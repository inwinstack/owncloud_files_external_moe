$(document).ready(function() {
    $(".files_external_info").click(function(){
        //OC.dialogs.info(t('files_external_moe', 'Please read operation document.'), t('files_external_moe', 'Instructions'));
        $.colorbox({
                opacity:0.4,
                transition:"elastic",
                speed:100,
                width:"70%",
                height:"70%",
                href: OC.filePath('files_external_moe', '', 'help.php'),
                //href: OC.filePath('firstrunwizard', '', 'wizard.php'),
                //onComplete : function(){
                //      if (!SVGSupport()) {
                //              replaceSVG();
                //      }
                //},
                //onClosed : function(){
                //      $.ajax({
                //      url: OC.filePath('firstrunwizard', 'ajax', 'disable.php'),
                //      data: ""
                //      });
                //}
        });
    });
});
