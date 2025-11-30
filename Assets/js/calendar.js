/**
 * Suppression List Calendar functionality
 */
var SupressionListCalendar = (function() {
    'use strict';

    // Initialize on document ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    function init() {
        // Only run if we're on the calendar page
        if (!document.querySelector('.supressionlist-calendar-wrapper')) {
            return;
        }

        // Mark already suppressed dates
        if (typeof markedDates !== 'undefined') {
            markedDates.forEach(function(date) {
                var cell = document.querySelector('.calendar-day[data-date="' + date + '"]');
                if (cell) {
                    cell.classList.add('marked');
                }
            });
        }

        // Add click handlers to all calendar day cells
        var dayCells = document.querySelectorAll('.calendar-day');
        dayCells.forEach(function(cell) {
            // Remove existing listeners to prevent duplicates
            var newCell = cell.cloneNode(true);
            cell.parentNode.replaceChild(newCell, cell);
            cell = newCell;

            cell.addEventListener('click', handleDateClick);

            // Add hover effect
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
        var cell = event.currentTarget;
        var date = cell.getAttribute('data-date');
        var isMarked = cell.classList.contains('marked');
        var action = isMarked ? 'FALSE' : 'TRUE';

        // Disable cell temporarily to prevent double-clicks
        cell.style.pointerEvents = 'none';

        // Build URL
        var url = baseUrl
            .replace('__ID__', suprListId)
            .replace('__DATE__', date)
            .replace('__ACTION__', action);

        // Get CSRF token from meta tag or use mauticAjax if available
        var csrfToken = '';
        var csrfMeta = document.querySelector('meta[name="mautic_csrf_token"]');
        if (csrfMeta) {
            csrfToken = csrfMeta.getAttribute('content');
        }

        // Make AJAX request
        var headers = {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        };

        // Add CSRF token to headers if available
        if (csrfToken) {
            headers['X-CSRF-Token'] = csrfToken;
        }

        fetch(url, {
            method: 'POST',
            headers: headers,
            credentials: 'same-origin'
        })
        .then(function(response) {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(function(data) {
            if (data.success) {
                // Toggle the marked class
                if (action === 'TRUE') {
                    cell.classList.add('marked');
                } else {
                    cell.classList.remove('marked');
                }
            } else {
                console.error('Failed to toggle date:', data);
                alert('Failed to update date. Please try again.');
            }
        })
        .catch(function(error) {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
        })
        .finally(function() {
            // Re-enable cell
            cell.style.pointerEvents = '';
        });
    }

    // Expose init function globally for AJAX reinitialization
    return {
        init: init
    };
})();
