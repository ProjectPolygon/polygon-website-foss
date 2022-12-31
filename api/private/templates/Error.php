<div class="card mx-auto" style="max-width:640px;">
	<div class="card-body text-center">
		<img src="/img/error.png">
		<h2 class="font-weight-normal"><?= $this->templateVariables["ErrorTitle"] ?></h2>
		<?= $this->templateVariables["ErrorMessage"] ?>
		<hr>
		<a class="btn btn-outline-primary mx-1 mt-1 py-1" onclick="window.history.back()">Go to Previous Page</a> 
		<a class="btn btn-outline-primary mx-1 mt-1 py-1" href="/">Return Home</a>
	</div>
</div>