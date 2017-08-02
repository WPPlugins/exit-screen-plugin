<?PHP
/*
Plugin Name: Exit Screen Plugin
Plugin URI: http://www.BlogsEye.com/
Description: Displays a web page whenever a user leaves the website.
Version: 1.3
Author: Keith P. Graham
Author URI: http://www.BlogsEye.com/

This software is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/


/************************************************************
* 	kpg_exit_screen_fixup()
*	Shows the javascript in the footer so that the links can be adjusted
*
*************************************************************/
if (!defined('ABSPATH')) exit;

function kpg_exit_screen_fixup() {
	// this is the Exit Screen functionality.
 	// since we are here we should remove the hooks so subsequent actions don't duplicate the javascript
	remove_filter( 'wp_footer', 'kpg_exit_screen_fixup' );
	remove_filter( 'get_footer', 'kpg_exit_screen_fixup' );
	// get the splash screen url
	$options=get_option('kpg_exit_screen');
	if (empty($options)||!is_array($options)) $options=array();
	$kpg_exit_msg="";
	$kpg_exit_url="";
	$kpg_frontpage='N';
	extract($options);
	if ($kpg_frontpage!='Y') $kpg_frontpage='N';
    if ($kpg_frontpage=='Y' && !is_front_page()) return;	
    if (empty($kpg_exit_url)) $kpg_exit_url="http://www.blogseye.com/buy-the-book/";
    if (empty($kpg_exit_msg)) $kpg_exit_msg="Please consider reading this one last message";
	$kpg_exit_msg=stripslashes(kpg_exit_msg);
 	$kpg_exit_msg=str_replace('"','&quot;',$kpg_exit_msg);
?>
<script language="javascript" type="text/javascript">
// <!--
/* <![CDATA[ */
// exit-screen-plugin
	
function  kpg_exsc_onclick(e) {
	return kpg_exsc_testclick(e,'A');
}
function  kpg_exsc_onsubmit(e) {
	return kpg_exsc_testclick(e,'FORM');
}
var kpg_exsc_testlink = document.createElement("a");
 
function  kpg_exsc_testclick(e,t) {
	// find out the tag that clicked the link
	kpg_exsc_unload_on=true;
	var e = e || window.event;
	if (e.target) {
		targ = e.target;
	} else if (e.srcElement) {
		targ = e.srcElement;
	}
	if (targ.nodeType == 3) // defeat Safari bug
		targ = targ.parentNode;
	var tag=targ;
	// find the "A" tag that triggered the onclick
	while (tag.tagName.toUpperCase()!='HTML' && tag.tagName.toUpperCase()!=t) {
		tag=tag.parentNode;
	}
	if (tag.tagName.toUpperCase()!=t) {
		// can't find the A tag - something is really screwed up
		//alert("not a tag");
		return true; // act like there is nothing we can do about it.
	}
	// first execute the old function
	if (typeof tag.kpgfunc == 'function') {
		var ansa=true;
		e.cancelBubble=true;
		ansa=tag.kpgfunc(e);
		//alert(ansa);
		if (!ansa) {
			e.returnValue=false;
			return false; // not leaving the page
		}
	}
	// good click - need to check if we run the unload script
	
	// don't worry about named anchors - we can't actually click them anyway.
	// href or form
	var fref=null;
	if (t=='A') fref=tag.href;
	if (t=='FORM') fref=tag.action;
	//alert("fref:"+fref);
	if (fref) { // an href exists so we need to check further
		// don't worry about scripts
		if (fref.toLowerCase().indexOf('javascript:')!=-1) {
			// executing a script - may or may not take us off the page, but I can't tell
			//alert("javascript:"+fref);
			kpg_exsc_unload_on=false;
			//window.onbeforeunload = null;
			return true;
		}
		// check the protocol
		if (fref.indexOf('http://')!=-1 && fref.indexOf('https://')!=-1 && fref.indexOf('ftp://')!=-1) {
			// the protocol is not the kind of thing we can handle
			//alert("protocol:"+fref);
			kpg_exsc_unload_on=false;
			//window.onbeforeunload = null;
			return true;
		}
		// valid protocol, not an anchor, not a script
		// check the target host.
		// in order to prevent problems when testing locally
		// get the hostname of the tag or form
		var hostname=null;
		if (t=='A') hostname=tag.hostname.toLowerCase();
		if (t=='FORM') {
			kpg_exsc_testlink.href=fref;
			hostname=kpg_exsc_testlink.hostname.toLowerCase();
		}
		//alert(hostname);
		if (hostname==location.hostname.toLowerCase()) {
			// we are not leaving the current host. No need to do the screen
			kpg_exsc_unload_on=false;
			//window.onbeforeunload = null;
			return true;
		}
		// if we got here then we must do a before unload
		// display stuff
		//alert("fref:"+fref);
		return true;
	}
		
	return true; // if we are here there is no href - we are out of our league.
}
function kpg_exsc_installLinks() {
	var tags = document.getElementsByTagName('A');
	for (var i = 0; i < tags.length; i++) {
		// add to check train.
		tag=tags[i];
		// add any defined old click functions
		if (typeof tag.onclick == 'function') {
			// there already is a click function
			tag.kpgfunc=tag.onclick; // save function for later
		} else {
			tag.kpgfunc=null;
		}
		// no make the onlick point to the new function
		tag.onclick=kpg_exsc_onclick;
	}
}

function kpg_exsc_installForms() {
	var tags = document.getElementsByTagName('FORM');
	for (var i = 0; i < tags.length; i++) {
		// add to check train.
		tag=tags[i];
		// is it defined - it should be
		if (tag) {
			// add any defined old click functions
			if (typeof tag.onsubmit == 'function') {
				// there already is a click function
				tag.kpgfunc=tag.onsubmit; // save function for later
			} else {
				tag.kpgfunc='';
			}
			// no make the onlick point to the new function
			tag.onsubmit=kpg_exsc_onsubmit;
		}
	}
}
function kpg_exsc_exitscreen_action(e) {
	kpg_exsc_installLinks();
	kpg_exsc_installForms();
	window.onbeforeunload = DisplayExitSplash;
}
// set the onload event
// only set it for firefox and IE
//if (( navigator.userAgent.indexOf('MSIE') !=-1 || navigator.userAgent.indexOf('Mozilla') !=-1 ) && navigator.userAgent.indexOf('Chrome') ==-1 && navigator.userAgent.indexOf('Safari') ==-1) { 

	if (document.addEventListener) {
		document.addEventListener("DOMContentLoaded", function(event) { kpg_exsc_exitscreen_action(event); }, false);
	} else if (window.attachEvent) {
		window.attachEvent("onload", function(event) { kpg_exsc_exitscreen_action(event); });
	} else {
		var oldFunc = window.onload;
		window.onload = function() {
			if (oldFunc) {
				oldFunc();
			}
				kpg_exsc_exitscreen_action('load');
			};
	}
//}
// write the iframe for non-mozilla people
if (navigator.userAgent.indexOf('MSIE') !=-1 || navigator.userAgent.indexOf('Chrome') !=-1  ) {
	document.open();
	document.write("<iframe id='kpg_exit_iframe' frameBorder='0' border='0' scrolling='no' src='<?php echo $kpg_exit_url; ?>' style='width:100%;height:10000px;border:0px;margin:0;padding:0;background-color:white;position:absolute;top:0;left:0;right:0;bottom:0;visibility:hidden;display:none;'></iframe>");
	document.close();
}
var kpg_exsc_unload_on=true;
var exitsplashmessage="<?php echo $kpg_exit_msg; ?>";
function DisplayExitSplash(e) {
	var e = e || window.event;
	if (!kpg_exsc_unload_on) {
		//e.returnValue=null;
		return;
	}
	
	if (navigator.userAgent.indexOf('MSIE') !=-1  || navigator.userAgent.indexOf('Chrome') !=-1 ) { 
		window.scrollTo(0,0);
		document.body.style.padding=0;
		document.body.style.margin=0;
		var nframe=document.getElementById('kpg_exit_iframe');
		//nframe.src='';
		nframe.style.display="block";
		nframe.style.visibility="visible";
	} else if (navigator.userAgent.indexOf('Mozilla') !=-1) {
		window.location.href="<?php echo $kpg_exit_url; ?>";
	} else {
		window.onbeforeunload = null;
		return;
	}
	window.onbeforeunload = null;
	return exitsplashmessage;
 }
 function kpg_exit_show() {

 }
/* ]]> */
// -->
</script>

<?php
}
function kpg_exit_screen_control()  {
	if(!current_user_can('manage_options')) {
		die('Access Denied');
	}
	$options=get_option('kpg_exit_screen');
	if (empty($options)||!is_array($options)) $options=array();
	$kpg_exit_msg="";
	$kpg_exit_url="";
	$kpg_frontpage="N";
	$msg="";
	extract($options);
	if ($kpg_frontpage!='Y') $kpg_frontpage='N';
	$nonce="";
	if (array_key_exists('kpg_exit_nonce',$_POST)&&array_key_exists('kpg_exit_url',$_POST)) {
		$nonce=$_POST['kpg_exit_nonce'];
		if (wp_verify_nonce($nonce,'kpg_exit_nonce')) {
			if (array_key_exists('kpg_exit_msg',$_POST)) $kpg_exit_msg=$_POST['kpg_exit_msg'];
			if (array_key_exists('kpg_exit_url',$_POST)) $kpg_exit_url=$_POST['kpg_exit_url'];
			if (array_key_exists('kpg_frontpage',$_POST)) $kpg_frontpage=$_POST['kpg_frontpage'];
			if ($kpg_frontpage!='Y') $kpg_frontpage='N';
			$options['kpg_exit_msg']=$kpg_exit_msg;
			$options['kpg_exit_url']=$kpg_exit_url;
			update_option('kpg_exit_screen',$options);
			$msg="Exit Screen Options Updated";
		}
	}
  	$nonce=wp_create_nonce('kpg_exit_nonce');

?>

<div class="wrap">
<h2>Exit Screen Plugin</h2>
<h3>The Exit Screen Plugin is installed and working correctly.</h3>

<?php
	if (!empty($msg)) echo "<h4>$msg</h4>";
?>
<div style="position:relative;float:right;width:35%;background-color:ivory;border:#333333 medium groove;padding-left:6px;">

<p>This plugin is free and I expect nothing in return. If you would like to support my programming, you can buy my book of short stories.</p><p>Some plugin authors ask for a donation. I ask you to spend a very small amount for something that you will enjoy. eBook versions for the Kindle and other book readers start at 99&cent;. The book is much better than you might think, and it has some very good science fiction writers saying some very nice things. <br/>
 <a target="_blank" href="http://www.blogseye.com/buy-the-book/">Error Message Eyes: A Programmer's Guide to the Digital Soul</a></p>
 <p>A link on your blog to one of my personal sites would also be appreciated.</p>
 <p><a target="_blank" href="http://www.WestNyackHoney.com">West Nyack Honey</a> (I keep bees and sell the honey)<br />
	<a target="_blank" href="http://www.cthreepo.com/blog">Wandering Blog </a> (My personal Blog) <br />
	<a target="_blank" href="http://www.cthreepo.com">Resources for Science Fiction</a> (Writing Science Fiction) <br />
	<a target="_blank" href="http://www.jt30.com">The JT30 Page</a> (Amplified Blues Harmonica) <br />
	<a target="_blank" href="http://www.harpamps.com">Harp Amps</a> (Vacuum Tube Amplifiers for Blues) <br />
	<a target="_blank" href="http://www.blogseye.com">Blog&apos;s Eye</a> (PHP coding) <br />
	<a target="_blank" href="http://www.cthreepo.com/bees">Bee Progress Beekeeping Blog</a> (My adventures as a new beekeeper) </p>
</div>
<p>The Exit Screen Plugin works by detecting when a reader of your blog closes the browser or loads another page. When the reader leaves your blog, a dialogue box will appear. Behind it a page of your choice will be seen. This is the last chance to catch your reader's attention.</p> 
<p>Below you can specify the URL of any web page that you want to have as the last page readers see. It will be behind a dialogue box and, in some browsers, it may be dimmed. It whould be a simple page with large type near the top. The page should have a clear message with information that will cause the reader to cancel the action of leaving your page. Since it is behind the dialogue box, which will be the focus of your reader's attention, it must be a real grabber with large colorful text above and below where the dialog box will appear. I would suggest hand crafting a static page with this in mind. Autostart audio or video will capture a reader's attention. Other options are to send readers to your Amazon page, an eBay auction listing, or other affiliate page.</p>


  <form method="post" action="">
    <input type="hidden" name="action" value="update" />
    <input type="hidden" name="kpg_exit_nonce" value="<?php echo $nonce;?>" />
<p>Enter your exit screen URL:<br/><input name="kpg_exit_url" type="text" value="<?php echo $kpg_exit_url ?>" size="64" />
</p><p>Enter your exit screen message (appears in IE message box):<br/><textarea name="kpg_exit_msg"><?php echo $kpg_exit_msg ?></textarea>
</p>
</p>
<p>Check if you want exit screen to appear only on your front page:<br/><input name="kpg_frontpage" type="checkbox" value="Y" <?php if ($kpg_frontpage=='Y') echo ' checked="true"'; ?> />
</p>

	<p class="submit"><input class="button-primary" value="Save Changes" type="submit"></p>

</form>
<p>The Exit Screen Plugin is ON when it is installed and enabled. To turn it off just disable the plugin from the plugin menu.</p>
<h4>For questions and support please check my website <a href="http://www.blogseye.com/i-make-plugins/exit-screen-plugin/">BlogsEye.com</a>.</h4>
<p>&nbsp;</p>
</div>
<?php
}
// no unistall because I have not created any meta data to delete.
function kpg_exit_screen_init() {
   add_options_page('Exit Screen', 'Exit Screen', 'manage_options',__FILE__,'kpg_exit_screen_control');
}
  // Plugin added to Wordpress plugin architecture
	add_action('admin_menu', 'kpg_exit_screen_init');	
	add_action( 'wp_footer', 'kpg_exit_screen_fixup' );
	add_action( 'get_footer', 'kpg_exit_screen_fixup' );

 	
?>