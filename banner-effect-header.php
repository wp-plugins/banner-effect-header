<?php
/*
Plugin Name: Banner Effect Header
Plugin URI: http://www.banner-effect.com
Version: 1.0.0
Description: Banner Effect Header is a plugin to integrate banners made with Banner Effect software on your WordPress site.
Author: Devsoft
Author URI: http://www.banner-effect.com
License: GPL
	Copyright 2012  Devsoft

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

//=============================================================
// ACTIONS
//=============================================================
add_action('wp_footer', 'display_banner');
add_action( 'admin_menu', 'banner_effect_menu' );
add_action("save_post",'BE_save_post');
//add_action('admin_menu', 'BE_myplugin_add_custom_box');
function BE_myplugin_add_custom_box() {
  if( function_exists( 'add_meta_box' )) {
		add_meta_box( 'BE_myplugin_sectionid', 'Banner Effect Header', 'BE_create_ch_form', 'page', 'advanced', 'high' );
		add_meta_box( 'BE_myplugin_sectionid', 'Banner Effect Header', 'BE_create_ch_form', 'post', 'advanced', 'high' );
   }else{
		add_action('dbx_post_advanced', 'BE_create_ch_form' );
		add_action('dbx_page_advanced', 'BE_create_ch_form' );
   }
}


//=============================================================
// FUNCTION FOR POST SPECIFIC BANNERS
//=============================================================
function BE_save_post()
{
	if(isset($_POST["BE_Banner"]))
	{
		global $post;
		$pid = $post->ID;
		$bid = $_POST["BE_Banner"];
		if($bid==-1)
		{
			delete_post_meta($pid,"BE_Name");
			delete_post_meta($pid,"BE_Path");
			delete_post_meta($pid,"BE_Width");
			delete_post_meta($pid,"BE_Height");
			delete_post_meta($pid,"BE_IsFlash");
			delete_post_meta($pid,"BE_IsHtml5");
		}
		else
		{
			$email = get_option( 'banner_effect_email' );
			if($email!="")
			{
				$banners = BE_GetBannerArray($email);
				copy_files($banners[$bid]);
				update_post_meta($pid,"BE_Name",$banners[$bid]->name);
				update_post_meta($pid,"BE_Path",$banners[$bid]->path);
				update_post_meta($pid,"BE_Width",$banners[$bid]->width);
				update_post_meta($pid,"BE_Height",$banners[$bid]->height);
				update_post_meta($pid,"BE_IsFlash",$banners[$bid]->isFlash);
				update_post_meta($pid,"BE_IsHtml5",$banners[$bid]->isHtml5);
			}
		}
	}
}

function BE_create_ch_form()
{
	print ("<b>Banner Effect Header for this Page</b><br/><br/>");
	print("This drop down shows a list of all banners available from Banner-Effect website for your account. If you want a specific banner for this page, choose one from the list below.");
    $email = get_option( 'banner_effect_email' );
	if($email=="")
	{
		print("<br/><br/><i>Banner Effect account not yet configured, please go <a href='options-general.php?page=BannerEffectOptions'>here</a> to set your email address.</i>");
	}
	else
	{
		//check if we have metas for this page
		global $post;
		$pid = $post->ID;
		$name = get_post_meta($pid, "BE_Name",true);	
		$banners = BE_GetBannerArray($email);
		print("<br/><br/><select name='BE_Banner' id='BE_Banner'>");
		if($name=="")
			print ("<option value='-1' selected>- Use default banner -</option>");
		else
			print ("<option value='-1'>- Use default banner -</option>");
		foreach($banners as $banner)
		{
			if($name==$banner->name)
				print ("<option value='$banner->id' selected>$banner->name ($banner->width x $banner->height)</option>");
			else
				print ("<option value='$banner->id'>$banner->name ($banner->width x $banner->height)</option>");
		}	
		print("</select>");
	}
}

//=============================================================
// FUNCTIONS TO RETRIEVE BANNERS FROM BANNER-EFFECT.COM
//=============================================================
class BE_Banner
{
	var $id;
	var $name;
	var $path;
	var $width;
	var $height;
	var $isFlash;
	var $isHtml5;
	var $assets;
}

function BE_GetBannerArray($mail)
{
	$lines = file("http://www.banner-effect.com/customer_banners/get_banner_list.php?email=".$mail);
	return BE_BannerTextToArray($lines);
}

function BE_BannerTextToArray($lines)
{
	$a = Array();
	if(count($lines)<1) return null;
	
	$num_banner = $lines[0];	
	$j=1;
	for($i=0;$i<$num_banner;$i++)
	{
		$b = new BE_Banner();
		$b->id = $i;
		
		$b->name = BE_cleanLine($lines[$j]);
		$b->path = BE_cleanLine($lines[$j+1]);
		$b->width = BE_cleanLine($lines[$j+2]);
		$b->height = BE_cleanLine($lines[$j+3]);
		$b->isFlash = BE_cleanLine($lines[$j+4]);
		$b->isHtml5 = BE_cleanLine($lines[$j+5]);
		$num_assets = BE_cleanLine($lines[$j+6]);
		$assets = Array();
		for($k=0;$k<$num_assets;$k++)
		{
			$assets[] = BE_cleanLine($lines[$j+7+$k]);
		}
		$j += $num_assets+7;
		$b->assets = $assets;
		$a[] = $b;
	}	
	return $a;
}

//=============================================================
// FUNCTION DISPLAYING THE BANNER IF NEEDED
//=============================================================
function display_banner()
{
	$p = get_option("BE_Path");
	$n = get_option("BE_Name");	
	$w = get_option("BE_Width");
	$h = get_option("BE_Height");
	$flash = get_option("BE_IsFlash");
	$html = get_option("BE_IsHtml5");
	
	//check if we have specific banner for this post.
	global $post;
	$pid = $post->ID;
	$post_name = get_post_meta($pid, "BE_Name",true);	
	if($post_name!="")
	{
		$p = get_post_meta($pid, "BE_Path",true);	
		$n = get_post_meta($pid, "BE_Name",true);	
		$w = get_post_meta($pid, "BE_Width",true);	
		$h = get_post_meta($pid, "BE_Height",true);	
		$flash = get_post_meta($pid, "BE_IsFlash",true);	
		$html = get_post_meta($pid, "BE_IsHtml5",true);	
	}
	if($n=="")
	{
		return;
	}
	if($html==1)
	{
		print "<script type=\"text/javascript\" ";
		print "src=\"$p/$n.js\">";
		print "</script>";
	}

	print("<script type=\"text/javascript\">");
	print("var all = document.getElementsByTagName(\"img\");");
	print("for (var i=0, max=all.length; i < max; i++) {");
    print("if(all[i].src == '"); 
	header_image();
	print("')");
	print("{");
	print("var mySpan = document.createElement('span');\n");
	
	$t = "";
	if($html==1)
	{
		$t .= ("<canvas id=\\\"$n\\\" onclick=\\\"this.focus();\\\" oncontextmenu=\\\"return false;\\\" width=$w height=$h tabindex=1 style=\\\"outline: none\\\">");
	}
	if($flash==1)
	{
		$t .=("<object classid=\\\"clsid:D27CDB6E-AE6D-11cf-96B8-444553540000\\\" width=\\\"$w\\\" height=\\\"$h\\\" id=\\\"$n\\\">");
		$t .= "<param name=\\\"movie\\\" value=\\\"$p/$n.swf\\\" />";
		$t .= "<param name= \\\"quality\\\" value=\\\"high\\\" />";
		$t .= "<param name=allowscriptaccess VALUE=\\\"always\\\">";
		$t .= "<!--[if !IE]>-->";
		$t .= "</object>";
		$t .= "<object data=\\\"$p/$n.swf\\\" ";
		$t .= "type=\\\"application/x-shockwave-flash\\\" width=\\\"$w\\\" height=\\\"$h\\\">";
		$t .= "<param name=allowscriptaccess VALUE=\\\"always\\\">";
		$t .= "<param name=\\\"quality\\\" value=\\\"high\\\" />";
		$t .="<!--<![endif]-->";
		$t .= "<a href=\\\"http://www.adobe.com/go/getflash\\\">";
		$t .= "<img src=\\\"http://www.adobe.com/images/shared/download_buttons/get_flash_player.gif\\\" alt=\\\"Get Adobe Flash player\\\"/>";
		$t .= "</a>";
		$t .="</object>";
	}
	if($html==1)
	{
		$t .="</canvas>";
	}
	print("mySpan.innerHTML = \"$t\";\n");
	print("all[i].parentNode.replaceChild(mySpan, all[i]);");
	print("");
	print("}");
	print("}");
	print("</script>");
}


//=============================================================
// FUNCTIONS ADMIN MENU
//=============================================================
function banner_effect_menu() {
	BE_myplugin_add_custom_box();
	add_options_page( 'Banner Effect Options', 'Banner Effect Header', 'manage_options', 'BannerEffectOptions', 'banner_effect_options' );
}

function banner_effect_options() {

    //must check that the user has the required capability 
    if (!current_user_can('manage_options'))
    {
      wp_die( __('You do not have sufficient permissions to access this page.') );
    }
	
    // variables for the field and option names 
    $opt_name = 'banner_effect_email';
	$be_opt_name = 'banner_effect_banner';
    $hidden_field_name = 'banner_effect_submit_hidden';
    $data_field_name = 'banner_effect_email';
	

    // Read in existing option value from database
    $opt_val = get_option( $opt_name );
	$be_current_banner = get_option($be_opt_name);

    // See if the user has posted us some information
    // If they did, this hidden field will be set to 'Y'
    if( isset($_POST[ $hidden_field_name ]) && $_POST[ $hidden_field_name ] == 'Y' ) {
        // Read their posted value
        $opt_val = $_POST[ $data_field_name ];
		$be_current_banner = $_POST[ "group1" ];

		if(BE_check_email($opt_val))
		{
			// Save the posted value in the database
			update_option( $opt_name, $opt_val );
			update_option( $be_opt_name,$be_current_banner);
			
			if($be_current_banner!=-1)
			{
				//retrieve array of banners
				$banners = BE_GetBannerArray($opt_val);
				
				//copy banner files
				if(copy_files($banners[$be_current_banner])===false)
				{
					print "<div class=\"error\"><p><strong>";
					$dir = WP_CONTENT_URL.'/banner-effect-banners/';
					_e('Problem when copying banner files. Check if directory \''.$dir.'\' exists and is writable.', 'BannerEffectSettings');
					print "</strong></p></div>";
					$opt_val = get_option( $opt_name );
					$be_current_banner = get_option($be_opt_name);
				}
				else
				{			
					$dir = WP_CONTENT_URL.'/banner-effect-banners/';
					// Put an settings updated message on the screen
					print "<div class=\"updated\"><p><strong>";
					_e('Settings saved and banner copied locally.', 'BannerEffectSettings' );
					print "</strong></p></div>";
					update_option("BE_Path",$dir."/".$banners[$be_current_banner]->name);							
					update_option("BE_Name",$banners[$be_current_banner]->name);
					update_option("BE_Width",$banners[$be_current_banner]->width);
					update_option("BE_Height",$banners[$be_current_banner]->height);
					update_option("BE_IsFlash",$banners[$be_current_banner]->isFlash);
					update_option("BE_IsHtml5",$banners[$be_current_banner]->isHtml5);
				}				
			}
			else
			{
				// Put an settings updated message on the screen
				print "<div class=\"updated\"><p><strong>";
				_e('Settings saved.', 'BannerEffectSettings' );
				print "</strong></p></div>";
				update_option("BE_Path","");							
				update_option("BE_Name","");
				update_option("BE_Width","");
				update_option("BE_Height","");
				update_option("BE_IsFlash","");
				update_option("BE_IsHtml5","");
			}
		}
		else
		{
			print "<div class=\"error\"><p><strong>";
			_e('Email is not valid.', 'BannerEffectSettings');
			print "</strong></p></div>";
			$opt_val = get_option( $opt_name );
			$be_current_banner = get_option($be_opt_name);
		}
		

	}

    // Now display the settings editing screen

    echo '<div class="wrap">';

    // header
	echo "<div id=\"icon-options-general\" class=\"icon32\"><br /></div>";
    echo "<h2>" . __( 'Banner Effect Header Settings', 'BannerEffectSettings' ) . "</h2>";

    // settings form
    
?>

<form name="form1" method="post" action="">
<input type="hidden" name="<?php echo $hidden_field_name; ?>" value="Y">

<table class="form-table">
<tr valign="top">
<th scope="row"><?php _e("Email Address:", 'BannerEffectSettings' ); ?></th><td>
<input type="text" name="<?php echo $data_field_name; ?>" value="<?php echo $opt_val; ?>" size="40">
<p class="description">Note: Please provide the email address you used when you uploaded the banners to Banner Effect website (free users) or the email you used when you registered the product.</p>
</td>
</tr>
<?PHP
	if($opt_val!="")
	{		
?>
<tr valign="top">
<th scope="row"><?php _e("Active banner:", 'BannerEffectSettings' ); ?></th><td>

<?PHP
	radio_banner_ex("-1","None (will use standard WordPress image)","-","-","",$be_current_banner);
	
	//retrieve list
	$banners = BE_GetBannerArray($opt_val);
	for($i=0;$i<count($banners);$i++)
	{
		$j = radio_banner($banners[$i],$be_current_banner);
	}
?>

<p class="description">Note: This list of banners is retrieved directly from Banner Effect website and will be copied here. You need to enter a valid email and have banners saved into your account to get the banner list.</p>
</td>
</tr>
<?PHP
}
?>
</table>

<p class="submit">
<input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes') ?>" />
</p>

</form>
</div>

<?php
 
}

function copy_files($banner)
{
	//check directory
	$dir = WP_CONTENT_DIR.'/banner-effect-banners/'.$banner->name;
	if(!file_exists($dir))
	{
			mkdir($dir,0777,true);
	}
	else
	{
		chmod($dir,0777);
	}
	
	$problem = false;
	if($banner->isFlash==1) 
	{
		if(copy($banner->path."/".$banner->name.".swf",$dir."/".$banner->name.".swf")==false)
		{
			$problem = true;
		}
	}
	if($banner->isHtml5==1) 
	{
		if(!file_exists($dir."/data"))
		{
			mkdir($dir."/data",0777,true);
		}
		else
		{
			chmod($dir."/data",0777);
		}
		if(!copy($banner->path."/".$banner->name.".js",$dir."/".$banner->name.".js")) $problem=true;
		foreach($banner->assets as $asset)
		{
			$asset2 = trim($asset);
			if(!copy($banner->path."/data/".$asset2,$dir."/data/".$asset2)) $problem=true;
		}
	}
	return !$problem;
}

function radio_banner($banner,$id_checked)
{
	$type = "";
	if($banner->isFlash==1) $type = "Flash";
	if($banner->isHtml5==1)
	{
		if($type=="")
		{
			$type = "Html5";
		}
		else
		{
			$type = "Flash and Html5";
		}
	}
	radio_banner_ex($banner->id,$banner->name,$banner->width." x ".$banner->height." pixels",$type,$banner->path."/index.html",$id_checked);
	$number_assets = $t[$i+6];
}

function radio_banner_ex($id,$title,$dim,$type,$preview,$id_checked)
{	
	print("<table ><tr><td>");
	print("<input type=\"radio\" name=\"group1\" value=\"$id\" ");
	if($id_checked==$id) print "checked";
	print " ></td><td>";
	print("<b>$title</b></br>");
	print("<b>Dimensions: </b>".$dim."<br/>");
	print("<b>Type: </b>".$type."<br/>");
	if($preview!="")
	{
		print("<b>Preview: </b><a target=\"_blank\" href=\"$preview\">Click here to preview the banner</a><br/>");
	}
	print("<br/></td></tr></table>");
	
}

//=============================================================
// HELPER FUNCTIONS
//=============================================================
function BE_cleanLine($l)
{
	$l = trim($l);
	$l = str_replace("\n","",$l);
	return $l;
}

function BE_check_email($e)
{
	if(strpos($e,"@")===false)
	{
		return false;
	}
	if(strpos($e,".")===false)
	{
		return false;
	}	
	if(strlen($e)<4)
	{
		return false;
	}
return true;	
}
?>