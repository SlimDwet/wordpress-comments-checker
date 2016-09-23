jQuery(document).ready(function() {
    var terms_container = jQuery('.terms_container');
    /**
     * Add term field
     * @type {[type]}
     */
    jQuery('#add_term_field').on('click', function(e) {
        e.preventDefault();
        var total_term = terms_container.find('.term_field').length;
        var add_term_html = '<p><label for="term'+total_term+'">'+objectL10n.term+'</label>&nbsp;';
        add_term_html += '<input type="text" id="term'+total_term+'" class="term_field" name="term[]">&nbsp;'
        add_term_html += '<a href="#" class="remove_term">'+objectL10n.remove_term+'</a>';
        add_term_html += '</p>';
        terms_container.append(add_term_html);
    });

    /**
     * Remove term field
     * @type {[type]}
     */
    jQuery('.terms_container').on('click', '.remove_term', function(e) {
        e.preventDefault();
        var elt = jQuery(this);
        elt.parent().remove();
    });
})
