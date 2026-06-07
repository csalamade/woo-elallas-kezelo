document.addEventListener('DOMContentLoaded', function() {
    var buttons = document.querySelectorAll('.wejk-submit-btn');
    buttons.forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            var form = this.closest('form');
            var checkboxes = form.querySelectorAll('input[name="wejk_returned_products[]"]:checked');
            if (checkboxes.length === 0) {
                e.preventDefault();
                alert(this.getAttribute('data-alert'));
                return false;
            }
            if (!confirm(this.getAttribute('data-confirm'))) {
                e.preventDefault();
                return false;
            }
        });
    });
});
