<?php
/**
 * GLOBALO - Redirection vers le point d'entrée public
 * Accédez à l'application via : /globalo/public/
 */
header('Location: public/', true, 302);
exit;
