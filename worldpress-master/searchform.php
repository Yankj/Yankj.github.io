<?php
/**
 * The search form file
 *
 *
 * @link 
 *
 * @package WorldPress
 * @since Worldpress 0.1
 */

?>

<form class="form-inline search-form" action="<?php echo home_url( '/' ); ?>" method="get">
  <div class="form-group">
    <div class="input-group">
      <input type="text" class="form-control" name="s" id="search" value="<?php the_search_query(); ?>" placeholder="Search This Blog">
    </div>
  </div>
  <button type="submit" class="btn btn-primary">Search</button>
</form>

