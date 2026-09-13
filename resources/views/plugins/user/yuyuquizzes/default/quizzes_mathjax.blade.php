@php
    $math_editor_tools = $math_editor_tools ?? false;
@endphp

@once
<script>
window.quizNormalizeLegacyMath = function (root) {
    if (!root || !document.createTreeWalker) {
        return;
    }

    var walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT);
    var nodes = [];

    while (walker.nextNode()) {
        nodes.push(walker.currentNode);
    }

    nodes.forEach(function (node) {
        var value = node.nodeValue || '';

        // 初期実装で二重保存された区切りを含むテキストだけを補正する。
        if (value.indexOf('\\\\[') === -1 &&
            value.indexOf('\\\\(') === -1) {
            return;
        }

        node.nodeValue = value.replace(/\\\\/g, '\\');
    });
};

window.MathJax = {
    tex: {
        inlineMath: [['\\(', '\\)']],
        displayMath: [['\\[', '\\]']]
    },
    startup: {
        ready: function () {
            window.quizNormalizeLegacyMath(document.body);
            MathJax.startup.defaultReady();
        }
    }
};

window.quizTypesetMath = function (root) {
    if (!window.MathJax || typeof window.MathJax.typesetPromise !== 'function') {
        return Promise.resolve();
    }

    var targets = root ? [root] : undefined;
    if (targets && typeof window.MathJax.typesetClear === 'function') {
        window.MathJax.typesetClear(targets);
    }

    return window.MathJax.typesetPromise(targets).catch(function (error) {
        console.error('MathJax typeset failed:', error);
    });
};
</script>
<script defer src="https://cdn.jsdelivr.net/npm/mathjax@4/tex-chtml.js"></script>
@endonce

@if ($math_editor_tools)
@once
<script>
(function () {
    window.quizMathEditor = {
        editor: null,
        bookmark: null,
        modal: null,

        open: function (editor) {
            this.editor = editor;
            this.bookmark = editor.selection.getBookmark(2, true);
            this.ensureModal();
            this.modal.style.display = 'flex';

            var input = this.modal.querySelector('[data-quiz-math-input]');
            input.value = '';
            input.focus();
            this.updatePreview();
        },

        close: function () {
            if (this.modal) {
                this.modal.style.display = 'none';
            }
            this.editor = null;
            this.bookmark = null;
        },

        ensureModal: function () {
            if (this.modal) {
                return;
            }

            var modal = document.createElement('div');
            modal.className = 'quiz-math-dialog';
            modal.innerHTML = [
                '<div class="quiz-math-dialog__panel" role="dialog" aria-modal="true" aria-labelledby="quiz-math-dialog-title">',
                '<h2 id="quiz-math-dialog-title" class="h5">数式を挿入</h2>',
                '<div class="form-group">',
                '<label>LaTeX</label>',
                '<textarea class="form-control" rows="4" data-quiz-math-input placeholder="例: x=\\frac{-b\\pm\\sqrt{b^2-4ac}}{2a}"></textarea>',
                '</div>',
                '<div class="form-check mb-3">',
                '<input class="form-check-input" type="checkbox" id="quiz-math-display-mode" data-quiz-math-display>',
                '<label class="form-check-label" for="quiz-math-display-mode">独立した行に表示する</label>',
                '</div>',
                '<div class="border rounded p-3 mb-3 quiz-math-dialog__preview" data-quiz-math-preview></div>',
                '<div class="text-right">',
                '<button type="button" class="btn btn-secondary mr-2" data-quiz-math-cancel>キャンセル</button>',
                '<button type="button" class="btn btn-primary" data-quiz-math-insert>挿入</button>',
                '</div>',
                '</div>'
            ].join('');

            document.body.appendChild(modal);
            this.modal = modal;

            var self = this;
            modal.querySelector('[data-quiz-math-input]').addEventListener('input', function () {
                self.updatePreview();
            });
            modal.querySelector('[data-quiz-math-display]').addEventListener('change', function () {
                self.updatePreview();
            });
            modal.querySelector('[data-quiz-math-cancel]').addEventListener('click', function () {
                self.close();
            });
            modal.querySelector('[data-quiz-math-insert]').addEventListener('click', function () {
                self.insert();
            });
            modal.addEventListener('click', function (event) {
                if (event.target === modal) {
                    self.close();
                }
            });
        },

        source: function () {
            var latex = this.modal.querySelector('[data-quiz-math-input]').value.trim();
            var display = this.modal.querySelector('[data-quiz-math-display]').checked;

            if (!latex) {
                return '';
            }

            return display ? '\\[' + latex + '\\]' : '\\(' + latex + '\\)';
        },

        updatePreview: function () {
            var preview = this.modal.querySelector('[data-quiz-math-preview]');
            var source = this.source();
            preview.textContent = source || 'ここに数式のプレビューを表示します。';

            if (source) {
                window.quizTypesetMath(preview);
            }
        },

        insert: function () {
            var source = this.source();
            if (!source || !this.editor) {
                return;
            }

            var editor = this.editor;
            var bookmark = this.bookmark;

            editor.focus();
            if (bookmark) {
                editor.selection.moveToBookmark(bookmark);
            }

            // TinyMCE自身の編集処理を通し、通常の文字として挿入する。
            // HTMLエスケープにより、LaTeXはHTMLとして解釈されない。
            editor.undoManager.transact(function () {
                editor.selection.setContent(editor.dom.encode(source));
            });

            editor.nodeChanged();
            editor.save();
            this.close();
        }
    };

    if (window.tinymce && !tinymce.PluginManager.get('quiz_math')) {
        tinymce.PluginManager.add('quiz_math', function (editor) {
            editor.ui.registry.addButton('quiz_math', {
                text: '数式',
                tooltip: 'LaTeX数式を挿入',
                onAction: function () {
                    window.quizMathEditor.open(editor);
                }
            });

            return {
                getMetadata: function () {
                    return { name: 'Quizzes MathJax input' };
                }
            };
        });
    }
})();
</script>

<style>
.quiz-math-dialog {
    position: fixed;
    inset: 0;
    z-index: 2000;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 1rem;
    background: rgba(0, 0, 0, 0.45);
}
.quiz-math-dialog__panel {
    width: min(640px, 100%);
    max-height: calc(100vh - 2rem);
    overflow-y: auto;
    padding: 1.25rem;
    background: #fff;
    border-radius: .25rem;
    box-shadow: 0 .5rem 1rem rgba(0, 0, 0, .3);
}
.quiz-math-dialog__preview {
    min-height: 5rem;
    overflow-x: auto;
    background: #f8f9fa;
}
</style>
@endonce
@endif
