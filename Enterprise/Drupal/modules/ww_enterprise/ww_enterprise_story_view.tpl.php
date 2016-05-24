<?php
	$path = base_path().drupal_get_path('theme', 'ourglobe');
	
	$imageatend = array(3);
?>

<div class="ww_enterprise_story_view">

	<?php
	
	if($node-> attachedimage && !in_array($node -> countid, $imageatend)) {
	?>
	<div class="image" style="float: left; margin-right: 25px;">
		<?php echo $node-> attachedimage;?>
		<p><?php echo $node->imagecaption;?></p>
	</div>
	<?php
	}
	?>
	
	<div class="teaser">
		<?php
		if (!$node->body) {
        	$read_more = "<span class='read-more'>" . l(t('[+]'), "node/$node->nid", array('absolute' => TRUE, 'html' => FALSE)) . '</span>';
        	print substr_replace($node-> teaser, $read_more, strrpos($node-> teaser, '</p>'), 0);
      	} else {
        	print $node-> teaser;
      	}
      ?>
	</div>

	<?php if($node -> body) { ?>
	<div class="writer">
		By: <?php echo $node-> writer;?><br />
		Updated, <?php echo strftime("%H:%M %d %b %Y", $node->changed);?>
		<div class="comment-count">
        	<?php print t('Comments'). ' (<a href="#comments">'. $node->comment_count .'</a>)'; ?>
      	</div>	
	</div>
	<?php } else { ?>
	
	<div class="writer">
		Updated, <?php echo strftime("%H:%M %d %b %Y", $node->changed);?>
		<div class="comment-count">
        	<?php print t('Comments'). ' (<a href="#comments">'. $node->comment_count .'</a>)'; ?>
      	</div>	
	</div>
	
	<?php } ?>
	
	<?php
	if($node-> attachedimage && in_array($node -> countid, $imageatend)) {
	?>
	<div class="image">
		<?php echo $node-> attachedimage;?>
		<p><?php echo $node->imagecaption;?></p>
	</div>
	<?php
	} elseif( is_array( $node-> movies ) && $node -> body && !$node-> attachedimage ) {
		$video = array_shift($node-> movies);
	?>
	
		<div id="movieplayer"></div>
		<script type='text/javascript' src='<?php echo $path;?>/swfobject.js'></script>
		<script type='text/javascript'>
		var s1 = new SWFObject('<?php echo base_path()?>mediaplayer/player.swf','movieplayer','400','300','9');
		s1.addParam('allowfullscreen','true');
		s1.addParam('allowscriptaccess','always');
		s1.addParam('flashvars','file=<?php echo base_path().$video->filepath?>');
		s1.write('movieplayer');
		</script>
	
	<?php
	}
	?>

	
	<div class="body"><?php echo $node-> body;?></div>
	
		<?php if( is_array( $node-> movies ) ): 
			foreach ($node-> movies as $key => $video) {
		?>
		
		<div id="movieplayer<?php echo $key;?>"></div>
		<script type='text/javascript' src='<?php echo $path;?>/swfobject.js'></script>
		<script type='text/javascript'>
		var s1 = new SWFObject('<?php echo base_path()?>mediaplayer/player.swf','movieplayer<?php echo $key;?>','400','300','9');
		s1.addParam('allowfullscreen','true');
		s1.addParam('allowscriptaccess','always');
		s1.addParam('flashvars','file=<?php echo base_path().$video->filepath?>');
		s1.write('movieplayer<?php echo $key;?>');
		</script>
		
		<?php }
		endif ?>
</div>