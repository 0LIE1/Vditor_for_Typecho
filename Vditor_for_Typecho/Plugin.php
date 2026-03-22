<?php

namespace TypechoPlugin\Vditor_for_Typecho;

use Typecho\Plugin\PluginInterface;
use Typecho\Widget\Helper\Form;
use Typecho\Widget\Helper\Form\Element\Radio;
use Typecho\Widget\Helper\Form\Element\Select;
use Typecho\Widget\Helper\Form\Element\Text;
use Widget\Archive;
use Widget\Base\Contents;
use Widget\Options;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * Vditor_for_Typecho
 *
 * 替换 Typecho 文章页的 Markdown 样式渲染。
 * 支持 Mermaid、ECharts、KaTeX、代码高亮、代码块主题切换、复制与下载按钮。
 * 由 Codex 开发。
 *
 * @package Vditor_for_Typecho
 * @author Codex
 * @version 1.0.0
 * @link https://github.com/0LIE1/Vditor_for_Typecho
 */
class Plugin implements PluginInterface
{
    private static function defaultSettings(): array
    {
        return [
            'version' => '3.11.2',
            'theme' => 'light',
            'mermaidVersion' => '10.9.1',
            'echartsVersion' => '5.5.1',
            'katexVersion' => '0.16.11',
            'highlightVersion' => '11.11.1',
            'codeThemePreset' => 'ice'
        ];
    }

    private static function getPluginConfig()
    {
        try {
            return Options::alloc()->plugin('Vditor_for_Typecho');
        } catch (\Throwable $e) {
            return (object) [];
        }
    }

    private static function ensurePluginConfig(): void
    {
        try {
            Options::alloc()->plugin('Vditor_for_Typecho');
        } catch (\Throwable $e) {
            \Widget\Plugins\Edit::configPlugin('Vditor_for_Typecho', self::defaultSettings());
        }
    }

    public static function activate()
    {
        self::ensurePluginConfig();
        \Typecho\Plugin::factory(Archive::class)->header = __CLASS__ . '::injectAssets';
        \Typecho\Plugin::factory(Contents::class)->contentEx = __CLASS__ . '::wrapMarkdown';

        return _t('Vditor_for_Typecho 已启用：替换 Typecho 文章页的 Markdown 样式渲染。');
    }

    public static function deactivate()
    {
    }

    public static function config(Form $form)
    {
        $plugin = self::getPluginConfig();

        $version = new Text(
            'version',
            null,
            '3.11.2',
            _t('Vditor 版本号'),
            _t('生产环境建议固定版本，例如 3.11.2。')
        );
        $form->addInput($version->addRule('required', _t('请填写 Vditor 版本号')));

        $theme = new Radio(
            'theme',
            [
                'light' => _t('light'),
                'wechat' => _t('wechat'),
                'ant-design' => _t('ant-design'),
                'dark' => _t('dark')
            ],
            'light',
            _t('内容主题'),
            _t('用于 Markdown 内容区的视觉样式。')
        );
        $form->addInput($theme);

        $mermaidVersion = new Text(
            'mermaidVersion',
            null,
            '10.9.1',
            _t('Mermaid 版本号'),
            _t('用于流程图、时序图、甘特图、思维导图等。')
        );
        $form->addInput($mermaidVersion->addRule('required', _t('请填写 Mermaid 版本号')));

        $echartsVersion = new Text(
            'echartsVersion',
            null,
            '5.5.1',
            _t('ECharts 版本号'),
            _t('用于渲染 lang-echarts 代码块。')
        );
        $form->addInput($echartsVersion->addRule('required', _t('请填写 ECharts 版本号')));

        $katexVersion = new Text(
            'katexVersion',
            null,
            '0.16.11',
            _t('KaTeX 版本号'),
            _t('用于渲染 Markdown 中的行内和块级公式。')
        );
        $form->addInput($katexVersion->addRule('required', _t('请填写 KaTeX 版本号')));

        $highlightVersion = new Text(
            'highlightVersion',
            null,
            '11.11.1',
            _t('highlight.js 版本号'),
            _t('用于普通代码块的语法高亮，例如 js、php、bash。')
        );
        $form->addInput($highlightVersion->addRule('required', _t('请填写 highlight.js 版本号')));

        $codeTheme = new Select(
            'codeThemePreset',
            self::codeThemeSelectOptions(),
            self::safeCodeTheme($plugin->codeThemePreset ?? 'ice'),
            _t('代码块风格'),
            self::codeThemePreviewMarkup(self::safeCodeTheme($plugin->codeThemePreset ?? 'ice'))
        );
        $form->addInput($codeTheme);
    }

    public static function personalConfig(Form $form)
    {
    }

    private static function codeThemePresets(): array
    {
        return [
            'ice' => [
                'label' => 'Ice',
                'hljs' => 'github',
                'shell' => '#f5f9ff',
                'toolbar' => '#edf4ff',
                'border' => '#d8e6ff',
                'text' => '#25324d',
                'muted' => '#5f6f8d',
                'buttonBg' => '#ffffff',
                'buttonText' => '#3559d7',
                'buttonBorder' => '#cddcff'
            ],
            'breeze' => [
                'label' => 'Breeze',
                'hljs' => 'atom-one-light',
                'shell' => '#f7fbfa',
                'toolbar' => '#ecf8f3',
                'border' => '#cfe9de',
                'text' => '#20423b',
                'muted' => '#4e7b71',
                'buttonBg' => '#ffffff',
                'buttonText' => '#1f7a67',
                'buttonBorder' => '#c5e4da'
            ],
            'sand' => [
                'label' => 'Sand',
                'hljs' => 'vs',
                'shell' => '#fbf6ee',
                'toolbar' => '#f4ebdd',
                'border' => '#e8d9c2',
                'text' => '#4c3926',
                'muted' => '#8b7150',
                'buttonBg' => '#fffaf3',
                'buttonText' => '#9b6d32',
                'buttonBorder' => '#e5d2b1'
            ],
            'forest' => [
                'label' => 'Forest',
                'hljs' => 'atom-one-dark',
                'shell' => '#0f1f1c',
                'toolbar' => '#17302b',
                'border' => '#214841',
                'text' => '#d9f4eb',
                'muted' => '#8dc7b7',
                'buttonBg' => '#183832',
                'buttonText' => '#ccf5e9',
                'buttonBorder' => '#2d5f55'
            ],
            'midnight' => [
                'label' => 'Midnight',
                'hljs' => 'a11y-dark',
                'shell' => '#0c1330',
                'toolbar' => '#121b40',
                'border' => '#25346e',
                'text' => '#e3eaff',
                'muted' => '#99a8df',
                'buttonBg' => '#182552',
                'buttonText' => '#dce6ff',
                'buttonBorder' => '#2e458f'
            ],
            'sunset' => [
                'label' => 'Sunset',
                'hljs' => 'night-owl',
                'shell' => '#2c1430',
                'toolbar' => '#3c1c40',
                'border' => '#6e3d72',
                'text' => '#ffe8f3',
                'muted' => '#d8a8c7',
                'buttonBg' => '#4a2350',
                'buttonText' => '#ffe5f5',
                'buttonBorder' => '#75457b'
            ]
        ];
    }

    private static function codeThemeSelectOptions(): array
    {
        $options = [];
        foreach (self::codeThemePresets() as $key => $preset) {
            $options[$key] = $preset['label'];
        }

        return $options;
    }

    private static function safeCodeTheme(string $theme): string
    {
        $presets = self::codeThemePresets();
        return isset($presets[$theme]) ? $theme : 'ice';
    }

    private static function codeThemePreviewMarkup(string $selected): string
    {
        $presets = self::codeThemePresets();
        $cards = '';

        foreach ($presets as $key => $preset) {
            $active = $key === $selected ? ' is-active' : '';
            $cards .= '<button type="button" class="vditor-theme-card' . $active . '" data-theme="' . $key . '"'
                . ' style="--card-shell:' . htmlspecialchars($preset['shell'], ENT_QUOTES, 'UTF-8') . ';'
                . '--card-toolbar:' . htmlspecialchars($preset['toolbar'], ENT_QUOTES, 'UTF-8') . ';'
                . '--card-border:' . htmlspecialchars($preset['border'], ENT_QUOTES, 'UTF-8') . ';'
                . '--card-text:' . htmlspecialchars($preset['text'], ENT_QUOTES, 'UTF-8') . ';'
                . '--card-muted:' . htmlspecialchars($preset['muted'], ENT_QUOTES, 'UTF-8') . ';'
                . '--card-button:' . htmlspecialchars($preset['buttonBg'], ENT_QUOTES, 'UTF-8') . ';'
                . '--card-button-text:' . htmlspecialchars($preset['buttonText'], ENT_QUOTES, 'UTF-8') . ';'
                . '--card-button-border:' . htmlspecialchars($preset['buttonBorder'], ENT_QUOTES, 'UTF-8') . ';">'
                . '<span class="vditor-theme-card__name">' . htmlspecialchars($preset['label'], ENT_QUOTES, 'UTF-8') . '</span>'
                . '<span class="vditor-theme-card__preview">'
                . '<span class="vditor-theme-card__toolbar"><em>js</em><i>复制</i><i>下载</i></span>'
                . '<span class="vditor-theme-card__code">const sum = (a, b) =&gt; a + b;</span>'
                . '</span>'
                . '</button>';
        }

        return <<<HTML
<style>
.vditor-theme-picker{margin-top:12px}
.vditor-theme-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;margin-top:10px}
.vditor-theme-card{display:block;width:100%;padding:12px;border:2px solid var(--card-border);border-radius:14px;background:#fff;cursor:pointer;text-align:left;transition:.2s ease}
.vditor-theme-card:hover,.vditor-theme-card.is-active{transform:translateY(-1px);box-shadow:0 10px 24px rgba(0,0,0,.08)}
.vditor-theme-card.is-active{outline:2px solid #467bff22}
.vditor-theme-card__name{display:block;margin-bottom:10px;font-weight:600;color:#24324a}
.vditor-theme-card__preview{display:block;border-radius:12px;overflow:hidden;background:var(--card-shell);border:1px solid var(--card-border)}
.vditor-theme-card__toolbar{display:flex;justify-content:space-between;gap:8px;padding:8px 10px;background:var(--card-toolbar);color:var(--card-muted);font-style:normal}
.vditor-theme-card__toolbar i{padding:2px 8px;border-radius:999px;border:1px solid var(--card-button-border);background:var(--card-button);color:var(--card-button-text);font-style:normal;font-size:12px}
.vditor-theme-card__code{display:block;padding:12px 10px 14px;color:var(--card-text);font-family:SFMono-Regular,Consolas,monospace;font-size:12px}
</style>
<div class="vditor-theme-picker">
    <p>灵感参考：<a href="https://ygria.site/prettier-codeblock-demo/" target="_blank" rel="noopener">Ygria 的代码块风格示例</a>。点击下方卡片可同步切换本插件的代码块主题。</p>
    <div class="vditor-theme-grid">{$cards}</div>
</div>
<script>
(function () {
    var select = document.querySelector('select[name="codeThemePreset"]');
    if (!select) return;
    var cards = document.querySelectorAll('.vditor-theme-card');
    cards.forEach(function (card) {
        card.addEventListener('click', function () {
            select.value = card.getAttribute('data-theme');
            cards.forEach(function (item) { item.classList.remove('is-active'); });
            card.classList.add('is-active');
        });
    });
    select.addEventListener('change', function () {
        cards.forEach(function (item) {
            item.classList.toggle('is-active', item.getAttribute('data-theme') === select.value);
        });
    });
})();
</script>
HTML;
    }

    public static function injectAssets(string $header, Archive $archive)
    {
        if (defined('__TYPECHO_ADMIN__')) {
            return;
        }

        $plugin = self::getPluginConfig();
        $version = self::safeVersion($plugin->version ?? '3.11.2');
        $theme = self::safeTheme($plugin->theme ?? 'light');
        $mermaidVersion = self::safeVersion($plugin->mermaidVersion ?? '10.9.1');
        $echartsVersion = self::safeVersion($plugin->echartsVersion ?? '5.5.1');
        $katexVersion = self::safeVersion($plugin->katexVersion ?? '0.16.11');
        $highlightVersion = self::safeVersion($plugin->highlightVersion ?? '11.11.1');
        $codeThemeKey = self::safeCodeTheme($plugin->codeThemePreset ?? 'ice');
        $codeTheme = self::codeThemePresets()[$codeThemeKey];
        $base = 'https://unpkg.com/vditor@' . rawurlencode($version) . '/dist';

        echo '<link rel="stylesheet" href="' . htmlspecialchars($base . '/index.css', ENT_QUOTES, 'UTF-8') . '">' . "\n";
        echo '<link rel="stylesheet" href="' . htmlspecialchars($base . '/css/content-theme/' . $theme . '.css', ENT_QUOTES, 'UTF-8') . '">' . "\n";
        echo '<link rel="stylesheet" href="https://unpkg.com/katex@' . htmlspecialchars(rawurlencode($katexVersion), ENT_QUOTES, 'UTF-8') . '/dist/katex.min.css">' . "\n";
        echo '<link rel="stylesheet" href="https://unpkg.com/@highlightjs/cdn-assets@' . htmlspecialchars(rawurlencode($highlightVersion), ENT_QUOTES, 'UTF-8') . '/styles/' . htmlspecialchars($codeTheme['hljs'], ENT_QUOTES, 'UTF-8') . '.min.css">' . "\n";
        echo '<style>:root{--vr-code-shell:' . htmlspecialchars($codeTheme['shell'], ENT_QUOTES, 'UTF-8') . ';--vr-code-toolbar:' . htmlspecialchars($codeTheme['toolbar'], ENT_QUOTES, 'UTF-8') . ';--vr-code-border:' . htmlspecialchars($codeTheme['border'], ENT_QUOTES, 'UTF-8') . ';--vr-code-text:' . htmlspecialchars($codeTheme['text'], ENT_QUOTES, 'UTF-8') . ';--vr-code-muted:' . htmlspecialchars($codeTheme['muted'], ENT_QUOTES, 'UTF-8') . ';--vr-code-button:' . htmlspecialchars($codeTheme['buttonBg'], ENT_QUOTES, 'UTF-8') . ';--vr-code-button-text:' . htmlspecialchars($codeTheme['buttonText'], ENT_QUOTES, 'UTF-8') . ';--vr-code-button-border:' . htmlspecialchars($codeTheme['buttonBorder'], ENT_QUOTES, 'UTF-8') . ';}.vditor-reset{padding:0;background:transparent}.vditor-reset table{display:table}.vditor-reset pre>code{white-space:pre}.vditor-reset img{max-width:100%;height:auto}.vditor-reset .vditor-taskblock{margin:1.6em 0;border:1px solid var(--vr-code-border);border-radius:16px;overflow:hidden;background:linear-gradient(180deg,var(--vr-code-shell) 0%,#fff 100%);box-shadow:0 12px 30px rgba(20,29,51,.08)}.vditor-reset .vditor-taskblock__toolbar{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:10px 14px;background:var(--vr-code-toolbar);border-bottom:1px solid var(--vr-code-border)}.vditor-reset .vditor-taskblock__label{font:600 12px/1.2 system-ui,sans-serif;letter-spacing:.08em;text-transform:uppercase;color:var(--vr-code-muted)}.vditor-reset .vditor-taskblock__meta{display:inline-flex;align-items:center;gap:8px;padding:6px 10px;border:1px solid var(--vr-code-button-border);border-radius:999px;background:var(--vr-code-button);color:var(--vr-code-button-text);font:500 12px/1 system-ui,sans-serif}.vditor-reset .vditor-taskblock__body{padding:14px 16px 16px}.vditor-reset .vditor-task-list{margin:0;padding-left:0;list-style:none}.vditor-reset .vditor-task-list .vditor-task-list{margin-top:.55em;margin-left:1.9em;padding-top:.15em}.vditor-reset .vditor-task-list-item{display:flex;align-items:flex-start;gap:.7em;padding:.55em .1em;border-radius:12px}.vditor-reset .vditor-task-list-item + .vditor-task-list-item{margin-top:.15em}.vditor-reset .vditor-task-list-item p{margin:0}.vditor-reset .vditor-task-list-item__checkbox{position:relative;display:inline-flex;align-items:center;justify-content:center;width:1.1rem;height:1.1rem;margin:.18em 0 0;flex:0 0 1.1rem;border:1.5px solid #94a3b8;border-radius:.35rem;background:#fff;box-shadow:inset 0 1px 0 rgba(255,255,255,.7);cursor:default}.vditor-reset .vditor-task-list-item__checkbox::after{content:"";width:.3rem;height:.58rem;border-right:2px solid transparent;border-bottom:2px solid transparent;transform:translateY(-.06rem) rotate(45deg)}.vditor-reset .vditor-task-list-item__checkbox--checked{border-color:#3b82f6;background:linear-gradient(180deg,#60a5fa 0%,#3b82f6 100%);box-shadow:0 6px 14px rgba(59,130,246,.18)}.vditor-reset .vditor-task-list-item__checkbox--checked::after{border-color:#fff}.vditor-reset .vditor-task-list-item--checked{background:rgba(59,130,246,.06)}.vditor-reset .vditor-task-list-item--checked > :not(.vditor-task-list-item__checkbox){opacity:.72}.vditor-reset .vditor-task-list-item--checked > p,.vditor-reset .vditor-task-list-item--checked > span,.vditor-reset .vditor-task-list-item--checked > div:first-of-type{text-decoration:line-through;text-decoration-thickness:1.5px}.vditor-echarts{width:100%;min-height:420px;margin:1.5em 0;border-radius:12px;background:#fff}.vditor-mermaid{margin:1.5em 0;padding:1.1em 0;overflow:auto}.vditor-mermaid svg{display:block;width:auto!important;max-width:none!important;height:auto;margin:0 auto}.vditor-render-error{margin:1em 0;padding:12px 14px;border-radius:10px;background:#fff1f0;color:#cf1322;font-size:14px;line-height:1.6}.vditor-codeblock{margin:1.6em 0;border:1px solid var(--vr-code-border);border-radius:16px;overflow:hidden;background:var(--vr-code-shell);box-shadow:0 12px 30px rgba(20,29,51,.08)}.vditor-codeblock__toolbar{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:10px 14px;background:var(--vr-code-toolbar);border-bottom:1px solid var(--vr-code-border)}.vditor-codeblock__label{font:600 12px/1.2 system-ui,sans-serif;letter-spacing:.08em;text-transform:uppercase;color:var(--vr-code-muted)}.vditor-codeblock__actions{display:flex;gap:8px}.vditor-codeblock__button{padding:6px 10px;border:1px solid var(--vr-code-button-border);border-radius:999px;background:var(--vr-code-button);color:var(--vr-code-button-text);font:500 12px/1 system-ui,sans-serif;cursor:pointer;transition:.2s ease}.vditor-codeblock__button:hover{transform:translateY(-1px)}.vditor-codeblock pre{margin:0;background:transparent!important}.vditor-codeblock pre code.hljs{display:block;padding:1.15em 1.25em;border-radius:0;background:transparent!important;line-height:1.75;color:var(--vr-code-text)}</style>' . "\n";
        echo '<script src="https://unpkg.com/mermaid@' . htmlspecialchars(rawurlencode($mermaidVersion), ENT_QUOTES, 'UTF-8') . '/dist/mermaid.min.js"></script>' . "\n";
        echo '<script src="https://unpkg.com/echarts@' . htmlspecialchars(rawurlencode($echartsVersion), ENT_QUOTES, 'UTF-8') . '/dist/echarts.min.js"></script>' . "\n";
        echo '<script src="https://unpkg.com/@highlightjs/cdn-assets@' . htmlspecialchars(rawurlencode($highlightVersion), ENT_QUOTES, 'UTF-8') . '/highlight.min.js"></script>' . "\n";
        echo '<script defer src="https://unpkg.com/katex@' . htmlspecialchars(rawurlencode($katexVersion), ENT_QUOTES, 'UTF-8') . '/dist/katex.min.js"></script>' . "\n";
        echo '<script defer src="https://unpkg.com/katex@' . htmlspecialchars(rawurlencode($katexVersion), ENT_QUOTES, 'UTF-8') . '/dist/contrib/auto-render.min.js"></script>' . "\n";
        echo <<<'SCRIPT'
<script>
(function () {
    function detectTaskMarker(node) {
        if (!node) {
            return null;
        }

        var match = node.textContent.match(/^\s*\[([ xX])\]\s+/);
        if (!match) {
            return null;
        }

        node.textContent = node.textContent.replace(/^\s*\[[ xX]\]\s+/, "");
        return {
            checked: match[1].toLowerCase() === "x"
        };
    }

    function convertTaskLists(root) {
        root.querySelectorAll("li").forEach(function (item) {
            if (item.classList.contains("vditor-task-list-item")) {
                return;
            }

            var firstMeaningful = null;
            Array.prototype.some.call(item.childNodes, function (node) {
                if (node.nodeType === Node.TEXT_NODE && node.textContent.trim() === "") {
                    return false;
                }

                firstMeaningful = node;
                return true;
            });

            if (!firstMeaningful) {
                return;
            }

            var marker = null;
            var checkboxHost = item;

            if (firstMeaningful.nodeType === Node.TEXT_NODE) {
                marker = detectTaskMarker(firstMeaningful);
            } else if (firstMeaningful.nodeType === Node.ELEMENT_NODE && firstMeaningful.tagName === "P") {
                checkboxHost = firstMeaningful;
                Array.prototype.some.call(firstMeaningful.childNodes, function (node) {
                    if (node.nodeType === Node.TEXT_NODE && node.textContent.trim() === "") {
                        return false;
                    }

                    if (node.nodeType === Node.TEXT_NODE) {
                        marker = detectTaskMarker(node);
                    }

                    return true;
                });
            }

            if (!marker) {
                return;
            }

            var checkbox = document.createElement("span");
            checkbox.setAttribute("aria-hidden", "true");
            checkbox.className = "vditor-task-list-item__checkbox";
            checkbox.classList.toggle("vditor-task-list-item__checkbox--checked", marker.checked);

            item.classList.add("vditor-task-list-item");
            item.classList.toggle("vditor-task-list-item--checked", marker.checked);

            if (item.parentElement) {
                item.parentElement.classList.add("vditor-task-list");
            }

            checkboxHost.insertBefore(checkbox, checkboxHost.firstChild);
        });
    }

    function wrapTaskBlocks(root) {
        root.querySelectorAll("ul.vditor-task-list").forEach(function (list) {
            if (list.closest(".vditor-taskblock")) {
                return;
            }

            if (list.parentElement && list.parentElement.closest("li.vditor-task-list-item")) {
                return;
            }

            var items = list.querySelectorAll(":scope > li.vditor-task-list-item");
            if (!items.length) {
                return;
            }

            var checkedCount = 0;
            items.forEach(function (item) {
                if (item.classList.contains("vditor-task-list-item--checked")) {
                    checkedCount += 1;
                }
            });

            var wrapper = document.createElement("div");
            wrapper.className = "vditor-taskblock";

            var toolbar = document.createElement("div");
            toolbar.className = "vditor-taskblock__toolbar";

            var label = document.createElement("span");
            label.className = "vditor-taskblock__label";
            label.textContent = "TASK LIST";

            var meta = document.createElement("span");
            meta.className = "vditor-taskblock__meta";
            meta.textContent = checkedCount + "/" + items.length + " completed";

            var body = document.createElement("div");
            body.className = "vditor-taskblock__body";

            toolbar.appendChild(label);
            toolbar.appendChild(meta);
            wrapper.appendChild(toolbar);

            list.parentNode.insertBefore(wrapper, list);
            wrapper.appendChild(body);
            body.appendChild(list);
        });
    }

    function ready(fn) {
        if (document.readyState !== "loading") {
            fn();
        } else {
            document.addEventListener("DOMContentLoaded", fn);
        }
    }

    function errorBox(message) {
        var div = document.createElement("div");
        div.className = "vditor-render-error";
        div.textContent = message;
        return div;
    }

    ready(function () {
        var roots = document.querySelectorAll(".vditor-reset");
        if (!roots.length) {
            return;
        }

        if (window.mermaid) {
            try {
                window.mermaid.initialize({
                    startOnLoad: false,
                    securityLevel: "loose",
                    theme: "default"
                });
            } catch (e) {}
        }

        var mermaidIndex = 0;

        roots.forEach(function (root) {
            convertTaskLists(root);
            wrapTaskBlocks(root);

            root.querySelectorAll("pre > code").forEach(function (code) {
                if (code.classList.contains("lang-mermaid") || code.classList.contains("lang-echarts")) {
                    return;
                }

                Array.prototype.slice.call(code.classList).forEach(function (className) {
                    if (className.indexOf("lang-") === 0) {
                        code.classList.add("language-" + className.slice(5));
                    }
                });

                if (window.hljs) {
                    window.hljs.highlightElement(code);
                }

                var pre = code.parentNode;
                if (!pre || (pre.parentNode && pre.parentNode.classList.contains("vditor-codeblock"))) {
                    return;
                }

                var wrapper = document.createElement("div");
                wrapper.className = "vditor-codeblock";

                var toolbar = document.createElement("div");
                toolbar.className = "vditor-codeblock__toolbar";

                var label = document.createElement("span");
                label.className = "vditor-codeblock__label";
                var language = "code";
                Array.prototype.slice.call(code.classList).some(function (className) {
                    if (className.indexOf("lang-") === 0) {
                        language = className.slice(5);
                        return true;
                    }
                    if (className.indexOf("language-") === 0) {
                        language = className.slice(9);
                        return true;
                    }
                    return false;
                });
                label.textContent = language.toUpperCase();

                var actions = document.createElement("div");
                actions.className = "vditor-codeblock__actions";

                function makeButton(text) {
                    var button = document.createElement("button");
                    button.type = "button";
                    button.className = "vditor-codeblock__button";
                    button.textContent = text;
                    return button;
                }

                var copyButton = makeButton("复制");
                copyButton.addEventListener("click", function () {
                    var source = code.textContent;
                    if (navigator.clipboard && navigator.clipboard.writeText) {
                        navigator.clipboard.writeText(source).then(function () {
                            copyButton.textContent = "已复制";
                            setTimeout(function () { copyButton.textContent = "复制"; }, 1500);
                        });
                    }
                });

                var downloadButton = makeButton("下载");
                downloadButton.addEventListener("click", function () {
                    var blob = new Blob([code.textContent], {type: "text/plain;charset=utf-8"});
                    var url = URL.createObjectURL(blob);
                    var link = document.createElement("a");
                    link.href = url;
                    link.download = "snippet." + (language === "code" ? "txt" : language);
                    document.body.appendChild(link);
                    link.click();
                    link.remove();
                    setTimeout(function () { URL.revokeObjectURL(url); }, 1000);
                });

                actions.appendChild(copyButton);
                actions.appendChild(downloadButton);
                toolbar.appendChild(label);
                toolbar.appendChild(actions);

                pre.parentNode.insertBefore(wrapper, pre);
                wrapper.appendChild(toolbar);
                wrapper.appendChild(pre);
            });

            root.querySelectorAll("pre > code.lang-mermaid").forEach(function (code) {
                var pre = code.parentNode;
                var host = document.createElement("div");
                host.className = "mermaid vditor-mermaid";
                pre.parentNode.replaceChild(host, pre);
                if (window.mermaid && window.mermaid.render) {
                    var renderId = "vr-mermaid-" + (mermaidIndex++);
                    window.mermaid.render(renderId, code.textContent).then(function (result) {
                        host.innerHTML = result.svg;
                        var svg = host.querySelector("svg");
                        if (svg) {
                            var viewBox = svg.getAttribute("viewBox");
                            if (viewBox) {
                                var parts = viewBox.split(/\s+/);
                                if (parts.length === 4) {
                                    svg.setAttribute("width", parts[2]);
                                    svg.style.width = parts[2] + "px";
                                    svg.style.maxWidth = "none";
                                }
                            }
                        }
                    }).catch(function (err) {
                        host.replaceWith(errorBox("Mermaid 渲染失败：" + err.message));
                    });
                }
            });

            root.querySelectorAll("pre > code.lang-echarts").forEach(function (code) {
                var pre = code.parentNode;
                var host = document.createElement("div");
                host.className = "vditor-echarts";
                pre.parentNode.replaceChild(host, pre);

                try {
                    var option = JSON.parse(code.textContent);
                    var chart = window.echarts.init(host);
                    chart.setOption(option);
                    window.addEventListener("resize", function () {
                        chart.resize();
                    });
                } catch (err) {
                    host.replaceWith(errorBox("ECharts 配置解析失败：" + err.message));
                }
            });

            if (window.renderMathInElement) {
                try {
                    window.renderMathInElement(root, {
                        delimiters: [
                            {left: "$$", right: "$$", display: true},
                            {left: "$", right: "$", display: false}
                        ],
                        throwOnError: false
                    });
                } catch (err) {
                    root.appendChild(errorBox("公式渲染失败：" + err.message));
                }
            }
        });
    });
})();
</script>
SCRIPT;
        echo "\n";
    }

    public static function wrapMarkdown(string $content, Contents $widget): string
    {
        if (defined('__TYPECHO_ADMIN__') || !$widget->isMarkdown) {
            return $content;
        }

        if (strpos($content, 'class="vditor-reset"') !== false) {
            return $content;
        }

        return '<div class="vditor-reset">' . $content . '</div>';
    }

    private static function safeVersion(string $version): string
    {
        return preg_match('/^[0-9A-Za-z._-]+$/', $version) ? $version : '3.11.2';
    }

    private static function safeTheme(string $theme): string
    {
        $allowed = ['light', 'wechat', 'ant-design', 'dark'];
        return in_array($theme, $allowed, true) ? $theme : 'light';
    }
}
