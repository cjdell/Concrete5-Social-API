<?php $blockID = 'twitter-feed-' . $this->blockObj->bID ?>

<script type="text/javascript">
  $(function($) {
    var blockID = '<?php echo $blockID ?>'; 
  });
</script>

<div id="<?php echo $blockID ?>" class="twitter-feed">
    <?php if ($title): ?>
    <h3><?php echo $title ?></h3>
    <?php endif ?>

    <p>Showing tweets for <strong><?php echo $screen_name ?></strong></p>

    <ul>
    <?php foreach ($response as $tweet): ?>
      <li>
        <?php echo $controller->getTextWithProcessedUrls($tweet) ?>
      </li>
    <?php endforeach ?>
    </ul>
</div>
