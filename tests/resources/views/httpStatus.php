
<?php if($message = ($obj->errorMessage ?? NULL)): ?>

<h1>PHP Fuse: Application Error</h1>
<p>We apologize for any inconvenience, but it seems that our website is experiencing some technical difficulties at the moment. Our team is working hard to address the issue and get everything back up and running as soon as possible. We understand how important our website is to our users, and we appreciate your patience while we work to resolve the problem. Please check back soon for updates on the status of our website. Thank you for your understanding.</p>

<pre>
	<?php echo $message; ?>
</pre>

<?php else: ?>
<h1><?php echo $obj->statusCode; ?> <?php echo $obj->phraseMessage; ?></h1>
<?php endif; ?>
