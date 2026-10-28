#!/usr/bin/env python3
"""Bounded two-process native module lifecycle lock proof; no orders or providers."""
import argparse
import json
import os
from pathlib import Path
import secrets
import selectors
import subprocess
import time

ROOT = Path(__file__).resolve().parents[1]
PREFIX = 'WOOSHOP_MODULE_RACE='


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--site', choices=['outdoor', 'smart-home'], default='outdoor')
    options = parser.parse_args()
    command = ['docker', 'compose', '--env-file', str(ROOT / '.local/compose.env'), '-f', str(ROOT / 'compose.yaml'),
               'exec', '-T', options.site, 'php', '/workspace/.local/cache/wp-cli.phar', '--allow-root', '--path=/var/www/html',
               'eval-file', '/workspace/tests/core/module-concurrency.php']
    run = secrets.token_hex(16)
    started = time.monotonic()
    work_deadline = started + 20
    hard_deadline = started + 30
    processes = {}
    events = []
    setup = None
    restored = None
    failure = None
    selector = selectors.DefaultSelector()
    buffers = {}

    def invoke(mode, extra=(), timeout=8):
        budget = min(timeout, max(0.1, hard_deadline - time.monotonic()))
        result = subprocess.run(command + [mode, run, *extra], cwd=ROOT, capture_output=True, text=True, timeout=budget)
        rows = [json.loads(line[len(PREFIX):]) for line in result.stdout.splitlines() if line.startswith(PREFIX)]
        if result.returncode != 0 or not rows or rows[-1].get('event') == 'failed':
            message = rows[-1].get('message', 'Worker command failed.') if rows else 'Worker command did not return a structured result.'
            raise RuntimeError(message)
        return rows[-1]

    def spawn(label, mode):
        process = subprocess.Popen(command + [mode, run], cwd=ROOT, stdin=subprocess.PIPE, stdout=subprocess.PIPE, stderr=subprocess.PIPE, bufsize=0)
        processes[label] = process
        for kind, stream in [('stdout', process.stdout), ('stderr', process.stderr)]:
            os.set_blocking(stream.fileno(), False)
            selector.register(stream, selectors.EVENT_READ, (label, kind))
            buffers[(label, kind)] = b''
        return process

    def pump(timeout):
        for key, _ in selector.select(max(0, timeout)):
            label, kind = key.data
            data = os.read(key.fileobj.fileno(), 4096)
            if not data:
                selector.unregister(key.fileobj)
                continue
            slot = (label, kind)
            buffers[slot] += data
            if len(buffers[slot]) > 65536:
                raise RuntimeError('Worker output exceeded its bounded size.')
            while b'\n' in buffers[slot]:
                line, buffers[slot] = buffers[slot].split(b'\n', 1)
                text = line.decode('utf-8', errors='replace')
                if kind == 'stdout' and text.startswith(PREFIX):
                    event = json.loads(text[len(PREFIX):])
                    event['worker'] = label
                    events.append(event)

    def wait_for(label, name):
        while time.monotonic() < work_deadline:
            found = next((event for event in events if event['worker'] == label and event.get('event') == name), None)
            if found:
                return found
            bad = next((event for event in events if event['worker'] == label and event.get('event') == 'failed'), None)
            if bad:
                raise RuntimeError(bad.get('message', 'Worker failed.'))
            if processes[label].poll() is not None:
                pump(0)
                found = next((event for event in events if event['worker'] == label and event.get('event') == name), None)
                if found:
                    return found
                raise RuntimeError('Worker exited before '+name)
            pump(min(0.1, work_deadline - time.monotonic()))
        raise RuntimeError('Bounded race deadline exceeded.')

    try:
        setup = invoke('setup')
        actor = spawn('command', 'command')
        wait_for('command', 'shared_lock_ready')
        changer = spawn('transition', 'transition')
        wait_for('transition', 'transition_attempt')
        hold_until = time.monotonic() + 1.25
        while time.monotonic() < hold_until:
            pump(min(0.05, hold_until - time.monotonic()))
            if any(event.get('event') == 'transition_committed' for event in events) or changer.poll() is not None:
                raise RuntimeError('Disable transition completed before the admitted command released its transaction.')
        actor.stdin.write(b'COMMIT\n')
        actor.stdin.close()
        wait_for('command', 'command_committed')
        changed = wait_for('transition', 'transition_committed')
        if changed['wait_seconds'] < 1.1 or not changed['new_work_denied'] or not changed['admitted_effect_committed']:
            raise RuntimeError('Lock ordering or post-transition denial was not observed.')
        for process in processes.values():
            if process.wait(timeout=max(0.1, work_deadline-time.monotonic())) != 0:
                raise RuntimeError('A race worker failed after its structured event.')
    except Exception as error:
        failure = str(error)
    finally:
        # Close only our own controlled worker input. PHP also has an 8-second stdin
        # deadline and 5-second session lock wait, so a failed driver cannot hold forever.
        for label, process in processes.items():
            if process.poll() is None and label == 'command' and process.stdin and not process.stdin.closed:
                try:
                    process.stdin.write(b'ABORT\n')
                    process.stdin.close()
                except (BrokenPipeError, OSError):
                    pass
        for process in processes.values():
            if process.poll() is None:
                try:
                    process.wait(timeout=min(5, max(0.1, hard_deadline-time.monotonic()-4)))
                except subprocess.TimeoutExpired:
                    failure = failure or 'Worker did not exit within its bounded lease; restoration requires review.'
        if setup and all(process.poll() is not None for process in processes.values()):
            try:
                restored = invoke('restore', [setup['prior_state'], str(setup['prepared_version'])], timeout=8)
            except Exception as error:
                failure = (failure+'; ' if failure else '')+'restoration: '+str(error)
        elif setup:
            failure = (failure+'; ' if failure else '')+'restoration not attempted while an owned worker remains active'
        selector.close()
    if not restored or restored['state'] != setup['prior_state']:
        failure = failure or 'Prior logical module state was not confirmed restored.'
    if not failure and restored['version'] <= setup['prior_version']:
        failure = 'Expected version advancement and retained transition audit were not observed.'
    result = {'suite': 'two-process module lifecycle locking', 'site': options.site, 'result': 'FAIL' if failure else 'PASS',
              'module': 'MOD-U003', 'run': run, 'setup': setup, 'events': events, 'restored': restored,
              'held_transaction_seconds': 1.25, 'elapsed_seconds': round(time.monotonic()-started, 3),
              'failure': failure, 'orders_or_provider_calls': 0, 'history_policy': 'No data wipe; admitted probe and transition audits retained.'}
    print(json.dumps(result, separators=(',', ':')))
    if failure:
        raise SystemExit(1)


if __name__ == '__main__':
    main()
