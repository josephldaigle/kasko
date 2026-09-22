/**
 * Created by Joe Daigle on 3/5/19.
 */

let AjaxForm = {
    /**
     * Handle form submission.
     *
     * Extracts all information required to submit the form from data-* attributes on the <form>.
     * Possible Options:
     *      data-url    the endpoint to send the request to
     *      data-method PUT, POST, GET
     *
     * @param event
     * @param successCallback function
     * @param errorCallback function
     */
    submit: function(event, successCallback, errorCallback) {
        event.preventDefault();
        event.stopPropagation();

        // select the <form> with jquery
        let form = $(event.currentTarget);

        // validate the form
        if (! $(form).hasClass('needs-validation')) {
            // show warning without validation
            console.warn("A form is being submitted without the `needs-validation` class. <form id=\"" + $(form).attr('id') + "\">");
        }

        // gather form data
        let formData = new FormData(form[0]);

        // invoke form data filter callback


        // output form data to console
        // for(var pair of formData.entries()) {
        //     console.log(pair[0]+ ', '+ pair[1]);
        // }


        if (event.currentTarget.checkValidity() === false) {
            form.addClass('was-validated');

            // enable form button
            $(form).find('button').attr('disabled', false);
            $(form).find('button').find('span.spinner').toggleClass('d-none');
        } else {

            let url = form.data('url');
            let method = form.data('method');

            $.ajax({
                url: url,
                method: method,
                data: formData,
                processData: false,
                contentType: false,
                context: $(form).attr('id')
            })
            .done(function(data, textStatus, jqXHR) {
                successCallback(data);
            })
            .fail(function(jqXHR, textStatus, errorThrown) {
                if (jqXHR.status === 400) {
                    console.error('400 response while submitting form.');
                    console.info('Response: ', jqXHR);
                    errorCallback({'level': 'alert-danger', 'message': 'Yikes! It looks like this is broken. You can still call us (478) 283-8900. We\'ll get this fixed soon.'});
                } else {
                    console.error('Unknown error while submitting form.');
                    errorCallback({'level': 'alert-danger', 'message': 'Yikes! It looks like this is broken. You can still call us (478) 283-8900. We\'ll get this fixed soon.'});
                }
            })
        }
    },
};

export default AjaxForm;