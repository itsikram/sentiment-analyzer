(function($){
    $(document).ready(function(){
        
        // remove checked attribute on uncheck clear cache checkbox
        $(document).on('change','#clearCache',function(e){
            
            if(! $(this).is(':checked')) {
                $(e.currentTarget).removeAttr('checked')
                $(e.currentTarget).val('0')
            }else {
                $(e.currentTarget).attr('checked')
                $(e.currentTarget).val('1')
            }
        })

        // handle clear cache button click
        $('#clearChacheNow').click(function(e)  {
            e.preventDefault();
            $.ajax({
                url:admin_ajax.url,
                type: 'POST',
                data: {
                    action: 'ajax_clear_sa_caches',
                    nonce: admin_ajax.nonce
                },
                success: function(response) {
                    console.log(response);
                    alert('Cache cleared successfully!')
                },
                error: function (error) {
                    
                    console.log('Error:', error);
                }
            })
        })

    });
})(jQuery)