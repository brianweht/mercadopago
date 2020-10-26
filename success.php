<!DOCTYPE html>
<html>
<head>
</head>
<body>
¡Listo! Se acreditó tu pago.
<br />
<h4>Información del pago</h4>
<table>
  <?php
    foreach($_REQUEST as $k=>$v)
	  echo '<tr><td><strong>'.$k.'</strong></td><td>'.$v.'</td></tr>';
  ?>
</table>
<br />
<a href="index.php">Inicio</a>
</body>
</html>