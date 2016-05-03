<?php 
require_once("../../../wp-load.php");
$thumbup=$_POST['method'];
$post_id=$_POST['id'];
if ($thumbup=='dislike') {
	$current=get_post_custom_values('dislike',$post_id)[0];
	update_post_meta($post_id,'dislike',$current+1);
} 
else if ($thumbup=='like') {
	$current=get_post_custom_values('like',$post_id)[0];
	update_post_meta($post_id,'like',$current+1);
}
$dislike_number=get_post_custom_values('dislike',$post_id)[0];
$like_number=get_post_custom_values('like',$post_id)[0];
header('Content-Type: application/json');
echo json_encode(array('like'=>$like_number,'dislike'=>$dislike_number))
?>