/**
 * 在列表页顶部工具栏追加按钮，把当前筛选结果的所有编辑页在新标签页打开。
 * 文案由 open_all_edit_tabs.php 通过 wp_localize_script 注入。
 */
(function () {
    var settings = window.npcinkSiteToolboxOpenAllEditTabs || {};
    var nav      = document.querySelector('.tablenav.top');
    if (!nav) {
        return;
    }

    var btn              = document.createElement('button');
    btn.type             = 'button';
    btn.className        = 'button';
    btn.style.marginLeft = '8px';
    btn.textContent      = settings.buttonLabel || '在新标签页打开全部编辑';

    var actions = nav.querySelector('.actions');
    (actions || nav).appendChild(btn);

    btn.addEventListener('click', function () {
        // row-actions 平时悬停才显示，但 DOM 里一直存在；
        // 每行的「编辑」链接只包含当前筛选与分页结果中的内容
        var links = document.querySelectorAll('#the-list .row-actions span.edit a');
        if (!links.length) {
            window.alert(settings.emptyMessage || '当前列表没有可编辑的内容。');
            return;
        }
        var blocked = 0;
        links.forEach(function (a) {
            if (!window.open(a.href, '_blank')) {
                blocked++;
            }
        });
        if (blocked) {
            window.alert((settings.blockedTemplate || '有 %d 个标签页被浏览器拦截，请在允许本站弹出式窗口后重试。').replace('%d', String(blocked)));
        }
    });
})();
