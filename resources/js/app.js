/**
 * Backend Dojo — frontend boot.
 *
 * CodeMirror 6 editor for task code entry, integrated with Livewire:
 *
 *   <div wire:ignore>
 *       <textarea wire:model="code" data-editor-source></textarea>   (kept hidden)
 *       <div data-editor-host data-editor-lang="php" data-editor-run="runTestsButton"></div>
 *   </div>
 *
 * The host lives inside `wire:ignore` so Livewire morphs never destroy the
 * CodeMirror instance. Every change is pushed back into the source textarea
 * (dispatched as a bubbling `input` event) so `wire:model` keeps working
 * exactly as it did with the raw textarea. CodeMirror is only created once
 * per host; after a `wire:navigate` page transition the DOM is rebuilt, so
 * editors are re-scanned on the `livewire:navigated` event.
 */
import { EditorView, basicSetup } from 'codemirror';
import { keymap } from '@codemirror/view';
import { indentWithTab } from '@codemirror/commands';
import { php } from '@codemirror/lang-php';
import { oneDark } from '@codemirror/theme-one-dark';

const LANG_FACTORIES = {
    php: () => php({ plain: true }),
    javascript: () => import('@codemirror/lang-javascript').then((m) => m.javascript()),
};

const EDITOR_FONT = "ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono', monospace";

/** Mount one editor host. */
function mountEditor(host) {
    if (host.dataset.cmReady === '1') {
        return;
    }

    const source = host.closest('[wire\\:ignore]')?.querySelector('[data-editor-source]')
        ?? host.parentElement?.querySelector('[data-editor-source]');

    if (!source) {
        return;
    }

    host.dataset.cmReady = '1';

    const lang = host.dataset.editorLang ?? 'php';
    const langFactory = LANG_FACTORIES[lang] ?? LANG_FACTORIES.php;
    const runButtonId = host.dataset.editorRun;

    // Push CodeMirror content into the (hidden) wire:model textarea.
    const syncToSource = EditorView.updateListener.of((update) => {
        if (!update.docChanged) {
            return;
        }
        source.value = update.state.doc.toString();
        source.dispatchEvent(new Event('input', { bubbles: true }));
    });

    const runShortcut = keymap.of([
        {
            key: 'Mod-Enter',
            run: () => {
                const btn = runButtonId
                    ? document.getElementById(runButtonId)
                    : host.querySelector('[data-editor-run-click]');
                if (btn) {
                    btn.click();
                }
                return true;
            },
        },
        { key: 'Mod-S', preventDefault: true, run: () => true },
    ]);

    const view = new EditorView({
        parent: host,
        doc: source.value,
        extensions: [
            basicSetup,
            langFactory(),
            oneDark,
            keymap.of([indentWithTab]),
            syncToSource,
            runShortcut,
            EditorView.theme({
                '&': { height: '100%', fontSize: '13px' },
                '.cm-scroller': { fontFamily: EDITOR_FONT, lineHeight: '1.6' },
                '.cm-content': { fontFamily: EDITOR_FONT },
                '&.cm-focused': { outline: 'none' },
            }),
        ],
    });

    host.dataset.cmView = '1'; // marker only; keep a handle for future use
    host._cmView = view;
}

/** Scan the document for editor hosts that are not yet initialised. */
function initEditors() {
    document.querySelectorAll('[data-editor-host]').forEach(mountEditor);
}

// Livewire 3 bundles its own Alpine; only the wiring below is ours.
document.addEventListener('DOMContentLoaded', initEditors);
document.addEventListener('livewire:navigated', initEditors);
