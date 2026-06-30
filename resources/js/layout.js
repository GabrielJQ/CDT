function getCookie(name) {
    var match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
    return match ? decodeURIComponent(match[2]) : null;
}

function setCookie(name, value, days) {
    var expires = new Date();
    expires.setTime(expires.getTime() + days * 24 * 60 * 60 * 1000);
    document.cookie = name + '=' + encodeURIComponent(value) + '; expires=' + expires.toUTCString() + '; path=/';
}

window.toggleSidebar = function () {
    var sidebar = document.getElementById('sidebar');
    var overlay = document.getElementById('sidebar-overlay');
    sidebar.classList.toggle('expanded');
    if (window.innerWidth < 1024) {
        overlay.classList.toggle('hidden');
    }
};

document.addEventListener('DOMContentLoaded', function () {
    if (window.innerWidth < 1024) {
        document.getElementById('sidebar').classList.remove('expanded');
    }

    var toggle = document.getElementById('presencia-toggle');
    var submenu = document.getElementById('presencia-submenu');
    var arrow = document.getElementById('presencia-arrow');
    if (toggle && submenu && arrow) {
        toggle.addEventListener('click', function () {
            submenu.classList.toggle('hidden');
            arrow.classList.toggle('-rotate-90');
        });
    }

    var cxcToggle = document.getElementById('cxc-toggle');
    var cxcSubmenu = document.getElementById('cxc-submenu');
    var cxcArrow = document.getElementById('cxc-arrow');
    if (cxcToggle && cxcSubmenu && cxcArrow) {
        cxcToggle.addEventListener('click', function () {
            cxcSubmenu.classList.toggle('hidden');
            cxcArrow.classList.toggle('-rotate-90');
        });
    }

    document.querySelectorAll('.nav-link').forEach(function (link) {
        link.addEventListener('click', function () {
            if (window.innerWidth < 1024) {
                var sidebar = document.getElementById('sidebar');
                var overlay = document.getElementById('sidebar-overlay');
                sidebar.classList.remove('expanded');
                overlay.classList.add('hidden');
            }
        });
    });

    var temaBtn = document.getElementById('tema-toggle');
    if (temaBtn) {
        var temaCookie = getCookie('tema');
        if (temaCookie === 'dark' || temaCookie === 'light') {
            var isDark = temaCookie === 'dark';
            document.documentElement.classList.toggle('dark', isDark);
            temaBtn.textContent = isDark ? '☀️' : '🌙';
        } else {
            temaBtn.textContent = document.documentElement.classList.contains('dark') ? '☀️' : '🌙';
        }
        temaBtn.addEventListener('click', function () {
            var dark = !document.documentElement.classList.contains('dark');
            document.documentElement.classList.toggle('dark', dark);
            temaBtn.textContent = dark ? '☀️' : '🌙';
            setCookie('tema', dark ? 'dark' : 'light', 30);
        });
    }

    var regionSelect = document.getElementById('region-select');
    var uoSelect = document.getElementById('uo-select');
    var regionForm = document.getElementById('region-form');

    function actualizarUoOptions() {
        if (uoSelect.disabled) return;
        var selectedRegion = regionSelect.value;
        var currentUo = uoSelect.value;
        uoSelect.innerHTML = '<option value="">📍 Todas UO</option>';
        if (selectedRegion === '') {
            uoSelect.disabled = true;
            return;
        }
        uoSelect.disabled = false;
        for (var i = 0; i < uoSelect.__allOptions.length; i++) {
            var opt = uoSelect.__allOptions[i];
            if (opt.getAttribute('data-region') === selectedRegion) {
                uoSelect.appendChild(opt.cloneNode(true));
            }
        }
        uoSelect.value = currentUo;
    }

    if (regionSelect && uoSelect) {
        uoSelect.__allOptions = [];
        for (var i = 0; i < uoSelect.options.length; i++) {
            uoSelect.__allOptions.push(uoSelect.options[i]);
        }
        actualizarUoOptions();
        regionSelect.addEventListener('change', function () {
            actualizarUoOptions();
            regionForm.submit();
        });
        uoSelect.addEventListener('change', function () {
            regionForm.submit();
        });
    }
});
