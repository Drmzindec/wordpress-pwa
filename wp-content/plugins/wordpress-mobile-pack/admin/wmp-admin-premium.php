<script type="text/javascript">
    if (window.WMPJSInterface && window.WMPJSInterface != null){
        jQuery(document).ready(function(){
            
            WMPJSInterface.localpath = "<?php echo plugins_url()."/".WMP_DOMAIN."/"; ?>";
            WMPJSInterface.init();
        });
    }
</script>
<div id="wmpack-admin">
    <div class="spacer-60"></div>
    <!-- set title -->
    <h1><?php echo WMP_PLUGIN_NAME;?></h1>
    <div class="spacer-20"></div>
	<div class="whats-new">
        
        <div class="left-side"> 
            <div class="details">
                <div class="spacer-10"></div>
                
                <h1>PREMIUM</h1>
                
                <div class="spacer-20"></div>
                <div class="showcase">
                	<img src="https://s3-eu-west-1.amazonaws.com/appticles-wmpack/dashboard/whats_new/images/whats-new.png">
                </div>
                <div class="spacer-20"></div>
                
                <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vivamus quis aliquet arcu. Suspendisse potenti. Sed vel nunc non arcu eleifend aliquet. Curabitur lacinia mi eget tristique aliquam. Nullam commodo magna at purus commodo porta. Vestibulum vel felis ut risus dictum posuere. Quisque ligula leo, vehicula at magna et, pretium commodo ex. Phasellus in aliquam sapien, at pretium sapien. Suspendisse et vehicula dui.
                <div class="spacer-20"></div>
                <form name="wmp_disconnect_form" id="wmp_disconnect_form" class="left" action="<?php echo admin_url('admin-ajax.php'); ?>?action=wmp_premium_disconnect" method="post">
                    <input type="hidden" name="wmp_disconnect_apikey" id="wmp_disconnect_apikey" placeholder="api key*" class="small indent" value="<?php echo WMobilePack::wmp_get_setting('premium_api_key');?>" />
                    <a class="btn blue smaller" href="javascript:void(0)" id="wmp_disconnect_send_btn">Disconnect</a>
                 </form>
                
            </div>
            <div class="spacer-10"></div>
            
                    
        </div>
        <div class="right-side"> 
            <!-- add news and updates -->
            <?php include_once('sections/wmp-news.php'); ?>

			<!-- add waitlist form -->
            <?php include_once('sections/wmp-waitlist.php'); ?>
 
            <!-- add feedback form -->
            <?php include_once('sections/wmp-feedback.php'); ?>
        </div>
    </div>
</div>

<script type="text/javascript">
    if (window.WMPJSInterface && window.WMPJSInterface != null){
        jQuery(document).ready(function(){
            window.WMPJSInterface.add("UI_disconnect",
                    "WMP_DISCONNECT",
                    {
                        'DOMDoc':       window.document,
                        'submitURL' :   '<?php echo WMP_APPTICLES_DISCONNECT;?>',
						'redirectTo' :  '<?php echo admin_url('admin.php?page=wmp-options');?>'
                    }, 
                    window
                );
        });
    }
</script>