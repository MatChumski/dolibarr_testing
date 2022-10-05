<?php include("db.php");

// Esto le dice a PHP que usaremos cadenas UTF-8 hasta el final
mb_internal_encoding('UTF-8');
 
// Esto le dice a PHP que generaremos cadenas UTF-8
mb_http_output('UTF-8');

// Se obtiene el ID del elemento donde estamos
if  (isset($_GET['id'])) {
  $idh = $_GET['id'];
  // Se extrae la hoja de vida con este ID en la base de datos
  $query = "SELECT * FROM llx_equipment_history WHERE rowid=$idh";
  $result = mysqli_query($conn, $query);
  if (mysqli_num_rows($result) == 1) {
    $row = mysqli_fetch_array($result);
    //se definen variables con los datos de la hoja de vida extraidos de la base de datos

    $idproduct=$row['fk_product'];
  }
}
//Lineas importantes para la correcta visualizacion del modulo
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) { $i--; $j--; }
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) $res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) $res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
// Try main.inc.php using relative path
if (!$res && file_exists("../main.inc.php")) $res = @include "../main.inc.php";
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res) die("Include of main fails");

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';

// Load translation files required by the page
$langs->loadLangs(array("garantias@garantias"));

$action = GETPOST('action', 'aZ09');

// Security check Ingreso Usuario
$socid = GETPOST('socid', 'int');
if (isset($user->socid) && $user->socid > 0)
{
	$action = '';
	$socid = $user->socid;
}

// HEADER MODULO
llxHeader("", $langs->trans("Area Garantias"));

//TITULO MODULO
print load_fiche_titre($langs->trans("Area Garantias"), '', 'garantias.png@garantias');

//al presionar el boton regresar se carga la pagina principal
if (isset($_POST['regresar'])) {
  header('Location: garantiasindex.php');
}
//header('Content-Type: text/html; charset=UTF-8');
?>
<html>
    <head><!--CABECERA-->
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8"> 
        <!--hojasde estilos de bootstrap4-->
        <link rel="stylesheet" href="styles.css">
        <link rel="stylesheet" href="bootstrap.css">
        <!--Estilos y plugins para los datables y generación de tablas con cuadros de busqueda-->
        <link rel="stylesheet" href="lib/datatables-1.10.20/css/dataTables.bootstrap4.min.css"/>
        <link rel="stylesheet" href="lib/datatables-1.10.20/plugins/responsive-2.2.3/css/responsive.bootstrap4.min.css"/>
        <script src="lib/datatables-1.10.20/js/jquery.dataTables.js"></script>
        <script src="lib/datatables-1.10.20/js/dataTables.bootstrap4.min.js"></script>
        <scrip src="lib/datatables-1.10.20/plugins/responsive-2.2.3/js/dataTables.responsive.min.js"></script>
        <scrip src="lib/datatables-1.10.20/plugins/responsive-2.2.3/js/responsive.bootstrap4.min.js"></scrip>
    </head>
    <script style="text/javascript">
        //Lanza una alerta antes de eliminar un registro
        function ConfirmDelete(){
            var respuesta = confirm("¿Está seguro de Eliminar este Registro de forma Permanente?\n\nDespués no podrá recuperarlo");
        if(respuesta == true){
                return true;
            }
            else{
                return false;
            }
        }
    </script>
    <body>
        <!-- Se envian el id de la hoja de vida y del producto registrado en la hoja de vida del formulario-->
        <form action="procedimientos.php?id=<?php echo $idh; ?>" method="POST">

            </br>
            <!--Link para entrar a visualizar la hoja de vida-->
            <a href="hojagarantia.php?id=<?php echo $idh; ?>" style="color:#08298A; font-size:15px;" title="Hoja de vida">Hoja de Vida</a>&nbsp;&nbsp;&nbsp;&nbsp;
            <!--Link para visualizar los documentos relacionados al equipo-->
            <a href="doc.php?id=<?php echo $idproduct?>&idh=<?php echo $idh?>" style="color:#08298A; font-size:15px;" title="Documentos Vinculados al Equipo">Documentos</a>&nbsp;&nbsp;&nbsp;&nbsp;
            <!--Link para visualizar los procedimientos relacionados al equipo-->
            <a href="procedimientos.php?id=<?php echo $idh; ?>" style="color:#08298A; font-size:15px; padding: 10px; border-top:2px solid; border-right:1px #999 solid; border-left:1px #999 solid;" title="Procedimientos de Mantenimiento">Procedimientos</a>&nbsp;&nbsp;&nbsp;&nbsp;
            <!--Link para visualizar las intervenciones realizadas al equipo-->
            <a href="intervenciones.php?id=<?php echo $idh; ?>" style="color:#08298A; font-size:15px;" title="Intevenciones internas">Intervenciones</a>&nbsp;&nbsp;&nbsp;&nbsp;
            <!--Link para visualizar los envios a garantia del equipo-->
            <a href="enviogarantia.php?id=<?php echo $idh; ?>" style="color:#08298A; font-size:15px;" title="Envíos a Garantía">Envíos a Garantía</a>&nbsp;&nbsp;&nbsp;&nbsp;
            <br><hr></hr>
            
            <!--Se imprime la referencia de la hoja de vida-->
            <h4><b><?php echo $row['ref']; ?></b></h4></br></br>
            
            <!-- TABLA DE PROCEDIMIENTO DE MANTENIMIENTO-->
            <b> Procedimiento de Mantenimiento</b>
            <br></br>
            <!-- Boton para crear actividad de mantenimiento-->
            <a href="crearmaintenance.php?id=<?php echo $idh?>" class="btn btn-secondary">
            <i class="fas fa-plus"></i><b> Crear Procedimiento</b></a>&nbsp;&nbsp;
            <!-- Boton para generar el pdf de la tabla de mantenimiento-->
            <div class="btn btn-danger"><a href="generarpdf.php?id=<?php echo $idh?>" style="color:#FFF; text-decoration:none; " target="_blank"><b>Generar PDF</b></a></div>
            <br></br><br></br>
            <table class="table" style="text-align: center" id="data"><!-- inicio de la tabla-->
                <thead><!-- Cabecera de la tabla-->
                    <tr>
                    <th>Actividades</th>
                    <th>Frecuencia</th>
                    <th>Imagen</th>
                    <th>Opciones</th>
                    </tr>
                </thead>
                <tbody><!-- Cuerpo de la Tabla Impresion de datos-->
                    <?php
                    //se consulta los procedimientos pertencientes a esta hoja de vida y que esten activos(aun no eliminados status=1)
                    $query = "SELECT * FROM llx_maintenance WHERE fk_product=$idproduct AND status=1";
                    $result = mysqli_query($conn, $query);
                    //while para imprimir todas las actividades de mantenimiento
                    while($row = mysqli_fetch_assoc($result)) { ?>
                        <tr>
                            <td align="left"><textarea cols="30" readonly><?php echo utf8_encode($row['activities']); //actividad del procedimiento?></textarea></td>
                            <td><?php echo utf8_encode($row['frecuency']); //frecuencia del procedimiento?></td>
                            <td><?php echo '<img src="'.$row['imagen'].'" width="100" height="100">'; //imagen si se desea adjuntar?></td>
                            <td>
                                <!-- Boton para eliminar id=id actividad idh = id hoja de vida-->
                                <a href="deletemaintenance.php?id=<?php echo $row['rowid']?>&idh=<?php echo $idh?>"><button type='button' 
                                class="btn btn-danger" onclick="return ConfirmDelete()">
                                <i class="far fa-trash-alt"></i></button><!-- icono eliminar-->
                                </a>
                            </td><!-- se cierra la columna con botones de eliminar y modificar-->
                        </tr><!-- se cierra la fila-->
                    <?php } ?>
                </tbody><!-- fin del body de la tabla-->
            </table><!-- fin de la tabla-->
            <br></br><br></br>
        </form>
    </body>
</html>
<?php
llxFooter();   
?>
<!-- Se crea una funcion de Javascript para cada tabla 
de esta forma se visualiza con un mejor diseño las tablas-->
<script type="application/javascript">
        $(function () {
            $('#data').DataTable({
                responsive: true,
                autoWidth: false,
            });
        });
</script>