/**
 * 在列表页顶部工具栏追加按钮，把当前筛选结果的所有编辑页或查看页在新标签页打开。
 * 文案由 open_all_edit_tabs.php 通过 wp_localize_script 注入。
 */
(function () {
    var settings = window.npcinkSiteToolboxOpenAllEditTabs || {};
    var nav      = document.querySelector('.tablenav.top');
    if (!nav) {
        return;
    }

    var actions = nav.querySelector('.actions');
    var target  = actions || nav;

    function appendButton(action, label, emptyMessage) {
        var btn              = document.createElement('button');
        btn.type             = 'button';
        btn.className        = 'button';
        btn.style.marginLeft = '8px';
        btn.textContent      = label;
        target.appendChild(btn);

        btn.addEventListener('click', function () {
            // row-actions 平时悬停才显示，但 DOM 里一直存在；
            // 选择器适用于文章、页面和注册了后台列表的自定义文章类型。
            var links = document.querySelectorAll('#the-list .row-actions span.' + action + ' a');
            if (!links.length) {
                window.alert(emptyMessage);
                return;
            }
            var blocked = 0;
            links.forEach(function (a) {
                // 先打开空白页，断开 opener 后再跳转；noopener 本身返回 null，不能用于判断拦截。
                var tab = window.open('', '_blank');
                if (!tab) {
                    blocked++;
                    return;
                }
                tab.opener        = null;
                tab.location.href = a.href;
            });
            if (blocked) {
                window.alert((settings.blockedTemplate || '有 %d 个标签页被浏览器拦截，请在允许本站弹出式窗口后重试。').replace('%d', String(blocked)));
            }
        });
    }

    if (settings.actions && settings.actions.edit) {
        appendButton('edit', settings.editButtonLabel || '在新标签页打开全部编辑', settings.editEmptyMessage || '当前列表没有可编辑的内容。');
    }
    if (settings.actions && settings.actions.view) {
        appendButton('view', settings.viewButtonLabel || '在新标签页打开全部查看', settings.viewEmptyMessage || '当前列表没有可查看的内容。');
    }
})();
