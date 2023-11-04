
<?php if ($message = ($obj->errorMessage ?? null)) : ?>
<h1>PHP Fuse: Application Error</h1>
<p>We apologize for any inconvenience, 
but it seems that our website is experiencing some technical difficulties 
at the moment.</p>

<pre>
    <?php echo $message; ?>
</pre>

<?php else : ?>
<h1><?php echo $obj->statusCode; ?> <?php echo $obj->phraseMessage; ?></h1>
<?php endif; ?>
