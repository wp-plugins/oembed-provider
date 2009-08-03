<?php
/*
Plugin Name: oEmbed Provider
Plugin URI: http://URI_Of_Page_Describing_Plugin_and_Updates
Description: An oEmbed provider for Wordpress
Version: 1.0
Author: Craig Andrews <candrews@integralblue.com>
Author URI: http://candrews.integralblue.com
*/

/*
    Copyright 2009  Craig Andrews  (candrews@integralblue.com)

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

if(function_exists('add_action')){
    // Running inside of Wordpress
    add_action('wp_head', 'add_oembed_links');
    
    function add_oembed_links(){
        if(is_single() || is_page() || is_attachment()){
            print '<link rel="alternate" type="application/json+oembed" href="' . plugins_url('oembed-provider/oembed-provider.php') . '?format=json&url=' . urlencode(get_permalink())  . '" />';
            print '<link rel="alternate" type="application/xml+oembed" href="' . plugins_url('oembed-provider/oembed-provider.php') . '?format=xml&url=' . urlencode(get_permalink())  . '" />';
        }
    }
}else{
    //Directly called (not by Wordpress)
    require('../../../wp-load.php');
    
    $url = $_GET['url'];
    $post_ID = url_to_postid($url);
    $post=get_post($post_ID);
    if(empty($post)){
        header('Status: 404');
        die("Not found");
    }else{
        $author = get_userdata($post->post_author);
        $oembed=array();
        $oembed['version']='1.0';
        $oembed['provider_name']=get_option('blogname');
        $oembed['provider_url']=get_option('home');
        $oembed['author_name']=$author->display_name;
        $oembed['author_url']=get_author_posts_url($author->ID, $author->nicename);
        $oembed['title']=$post->post_title;
        switch(get_post_type($post)){
            case 'attachment':
                if(substr($post->post_mime_type,0,strlen('image/'))=='image/'){
                    $oembed['type']='photo';
                }else{
                    $oembed['type']='link';
                }
                $oembed['url']=wp_get_attachment_url($post->ID);
                break;
            case 'post':
            case 'page':
                $oembed['type']='link';
                $oembed['html']=empty($post->post_excerpt)?$post->post_content:$post->post_excerpt;
                break;
            default:
                header('Status: 501');
                die('oEmbed not supported for posts of type \'' . $post->type . '\'');
                break;
        }

        $format = $_GET['format'];
        switch($format){
            case 'json':
                header('Content-Type: application/json; charset=' . get_option('blog_charset'), true);
                print(json_encode($oembed));
                break;
            case 'xml':
                header('Content-Type: text/xml; charset=' . get_option('blog_charset'), true);
                print '<?xml version="1.0" encoding="' . get_option('blog_charset') . '" standalone="yes"?>';
                print '<oembed>';
                foreach(array_keys($oembed) as $element){
                    print '<' . $element . '><![CDATA[' . $oembed[$element] . ']]></' . $element . '>';
                }
                print '</oembed>';
                break;
            default:
                header('Status: 501');
                die('Format \'' . $format . '\' not supported');
        }
    }
}
?>
