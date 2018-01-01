<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>[@Title]</title>
	
	<link rel="shortcut icon" href="favicon.ico">
	<link rel="stylesheet" type="text/css" href="css/bootstrap.min.css" />
	<link rel="stylesheet" type="text/css" href="css/local.css" />

	<script src="js/jquery-1.10.2.min.js"></script>
	<script src="js/bootstrap.min.js"></script>
[@Include]
</head>
<body>
	<div id="wrapper">
		<nav class="navbar navbar-inverse navbar-fixed-top">
			<div class="navbar-header">
				<button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-ex1-collapse">
					<span class="sr-only">Toggle navigation</span>
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
				</button>
				<div class="navbar-brand">[@Title]</div>
				
			</div>
			<div class="collapse navbar-collapse navbar-ex1-collapse">
				<ul id="active" class="nav navbar-nav side-nav">
[@Menu]
				</ul>
			</div>
		</nav>

		<div id="page-wrapper">
			<div class="row">
				<div class="col-lg-12">
				<!-- CONTENT -->
[@Errors]
[@Content]		
				</div>
			</div>
		</div>
	</div><!-- /#wrapper -->
</body>
</html>