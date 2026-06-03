/**
 * Suppression List Calendar functionality
 */
var SupressionListCalendar = (function() {
    'use strict';

    var toastTimer = null;

    function showToast(dateStr, action, listName) {
        var toast = document.getElementById('supr-toast');
        if (!toast) { return; }

        var parts   = dateStr.split('-');
        var months  = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
        var label   = parseInt(parts[2], 10) + ' ' + months[parseInt(parts[1], 10) - 1] + ' ' + parts[0];
        var isAdd   = action === 'TRUE';
        var name    = listName || 'Suppression list';

        toast.className = 'show ' + (isAdd ? 'toast-add' : 'toast-remove');
        toast.innerHTML =
            '<i class="toast-icon ri-calendar-' + (isAdd ? 'close' : 'check') + '-line"></i>' +
            '<div class="toast-body">' +
                '<strong>' + escHtml(name) + '</strong>' +
                '<span>' + label + (isAdd ? ' is now suppressed' : ' is no longer suppressed') + '</span>' +
            '</div>';

        clearTimeout(toastTimer);
        toastTimer = setTimeout(function() {
            toast.classList.remove('show');
        }, 3200);
    }

    function escHtml(str) {
        return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    // Initialize on document ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    function init() {
        if (!document.querySelector('.supressionlist-calendar-wrapper')) {
            return;
        }

        if (typeof markedDates !== 'undefined') {
            markedDates.forEach(function(date) {
                var cell = document.querySelector('.calendar-day[data-date="' + date + '"]');
                if (cell) {
                    cell.classList.add('marked');
                }
            });
        }

        var dayCells = document.querySelectorAll('.calendar-day');
        dayCells.forEach(function(cell) {
            var newCell = cell.cloneNode(true);
            cell.parentNode.replaceChild(newCell, cell);
            cell = newCell;

            cell.addEventListener('click', handleDateClick);

            cell.addEventListener('mouseenter', function() {
                if (!this.classList.contains('marked')) {
                    this.classList.add('hover');
                }
            });

            cell.addEventListener('mouseleave', function() {
                this.classList.remove('hover');
            });
        });
    }

    function handleDateClick(event) {
        var cell     = event.currentTarget;
        var date     = cell.getAttribute('data-date');
        var isMarked = cell.classList.contains('marked');
        var action   = isMarked ? 'FALSE' : 'TRUE';

        cell.style.pointerEvents = 'none';

        var url = baseUrl
            .replace('__ID__', suprListId)
            .replace('__DATE__', date)
            .replace('__ACTION__', action);

        var headers = { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' };
        var csrfMeta = document.querySelector('meta[name="mautic_csrf_token"]');
        if (csrfMeta) {
            headers['X-CSRF-Token'] = csrfMeta.getAttribute('content');
        }

        fetch(url, { method: 'POST', headers: headers, credentials: 'same-origin' })
            .then(function(response) {
                if (!response.ok) { throw new Error('Network error'); }
                return response.json();
            })
            .then(function(data) {
                if (data.success) {
                    if (action === 'TRUE') {
                        cell.classList.add('marked');
                    } else {
                        cell.classList.remove('marked');
                    }
                    showToast(date, action, typeof suprListName !== 'undefined' ? suprListName : '');
                } else {
                    alert('Failed to update date. Please try again.');
                }
            })
            .catch(function() {
                alert('An error occurred. Please try again.');
            })
            .finally(function() {
                cell.style.pointerEvents = '';
            });
    }

    return { init: init };
})();
