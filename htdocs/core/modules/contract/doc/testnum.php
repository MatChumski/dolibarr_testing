<?php

// Determinar el texto equivalente a la unidad, junto con la posición del dígito (miles, millones)
function whatUnit($unit, $pos, $prevPos)
{
	switch($unit)
	{
		case 1:
			if ($prevPos || $pos != "mil")
			{
				return "un $pos";
			} 
			else 
			{
				return $pos;
			}
			break;
		case 2:
			return "dos $pos";
			break;
		case 3:
			return "tres $pos";
			break;
		case 4:
			return "cuatro $pos";
			break;
		case 5:
			return "cinco $pos";
			break;
		case 6:
			return "seis $pos";
			break;
		case 7:
			return "siete $pos";
			break;
		case 8:
			return "ocho $pos";
			break;
		case 9:
			return "nueve $pos";
			break;
		default:
			return "";
			break;
	}
}

// Determinar el texto equivalente a la decena, junto con la posición del dígito (miles, millones)
function whatDec($dec, $unit, $pos)
{
	$curUnit = whatUnit($unit, "", true);
	switch($dec)
	{
		// Para el caso particular de la decena 1 (10), se evalúan todos los posibles valores
		case 1:
			if ($unit != 0){
				switch($unit)
				{
					case 1:
						return "once $pos";
						break;
					case 2:
						return "doce $pos";
						break;
					case 3:
						return "trece $pos";
						break;
					case 4:
						return "catorce $pos";
						break;
					case 5:
						return "quince $pos";
						break;
					case 6:
						return "dieciseis $pos";
						break;
					case 7:
						return "diecisiete $pos";
						break;
					case 8:
						return "dieciocho $pos";
						break;
					case 9:
						return "diecinueve $pos";
						break;
				}
			} 
			else 
			{
				return "diez $pos";
			}
			break;
		case 2:
			return whatNext($curUnit, $pos, "veinti", "veinte");
			break;
		case 3:
			return whatNext($curUnit, $pos, "treinta y ", "treinta");
			break;
		case 4:
			return whatNext($curUnit, $pos, "cuarenta y ", "cuarenta");
			break;
		case 5:
			return whatNext($curUnit, $pos, "cincuenta y ", "cincuenta");
			break;
		case 6:
			return whatNext($curUnit, $pos, "sesenta y ", "sesenta");
			break;
		case 7:
			return whatNext($curUnit, $pos, "setenta y ", "setenta");
			break;
		case 8:
			return whatNext($curUnit, $pos, "ochenta y ", "ochenta");
			break;
		case 9:
			return whatNext($curUnit, $pos, "noventa y ", "noventa");
			break;
		default:
			return "";
			break;
	}
}

// Este método determina si se debe escribir el número normal, o con un conector
function whatNext($next, $pos, $num, $numFull)
{
	if ($next != "" && $next != "0"){
		return "$num";
	} 
	else 
	{
		return "$numFull $pos";
	}
}

// Determinar el texto equivalente a la centena, junto con la posición del dígito (miles, millones)
function whatCent($cent, $next, $pos)
{
	$curDec = whatDec($next, 0, "");
	switch($cent)
	{
		case 1:
			return whatNext($curDec, $pos, "ciento ", "cien");
			break;
		case 2:
			return whatNext($curDec, $pos, "doscientos ", "doscientos");
			break;
		case 3:
			return whatNext($curDec, $pos, "trescientos ", "trescientos");
			break;
		case 4:
			return whatNext($curDec, $pos, "cuatrocientos ", "cuatrocientos");
			break;
		case 5:
			return whatNext($curDec, $pos, "quinientos ", "quinientos");
			break;
		case 6:
			return whatNext($curDec, $pos, "seiscientos ", "seiscientos");
			break;
		case 7:
			return whatNext($curDec, $pos, "setecientos ", "setecientos");
			break;
		case 8:
			return whatNext($curDec, $pos, "ochocientos ", "ochocientos");
			break;
		case 9:
			return whatNext($curDec, $pos, "novecientos ", "novecientos");
			break;
		default:
			return "";
			break;
	}
}

 

function whatNum($num, $type, $next, $pos)
{
	if ($type == "c")
	{
		return whatCent($num, $next, $pos);
	}
	if ($type == "d")
	{
		return whatDec($num, $next, $pos);	
	}
	if ($type = "u")
	{
		return whatUnit($num, $pos, false);
	}
}

function numToText($num)
{
	// Arreglo para guardar todas las posiciones del número
	$desc = array(
		array('u'),
		array('d'),
		array('c'),
		array('um'),
		array('dm'),
		array('cm'),
		array('uM'),
		array('dM'),
		array('cM')
	);

	$digits = strlen($num);
	// Recorre los dígitos de atrás hacia adelante, y guarda los valores en el arreglo $desc
	for ($i = $digits - 1; $i >= 0; $i--)
    {
        $desc[$digits - 1 - $i][] = intval($num[$i]);
    }

	$result = "";

	// Cuando el valor correspondiente a las decenas es 1, se considera la excepción para los
	// valores que siguen al 10, que va a verificarse cuando las unidades se vayan a convertir a texto
	$dM10 = false;
	$dm10 = false;
	$d10 = false;

	// Recorre el arreglo $desc de atrás hacia adelante, y verifica cada dígito
    for ($i = count($desc); $i >= 0; $i--)
	{
		$type = $desc[$i][0];
		$curNum = $desc[$i][1];
		$nextNum = $desc[$i-1][1];

		// MILLONES

		// Centena de Millón
		if ($type == "cM")
		{
			$result .= whatNum($curNum, "c", $nextNum, "millones") . " ";
		}

		// Decena de Millón
		if ($type == "dM")
		{
			$result .= whatNum($curNum, "d", $desc[$i-1][1], "millones") . " ";
			if ($curNum == 1)
			{
				$dM10 = true;
			}
		}

		// Unidad de Millón
		if ($type == "uM")
		{
			if (!$dM10)
			{
				if ($curNum == 1)
				{
					$result.= whatNum($curNum, "u", 0, "millon") . " ";
				}
				else
				{
					$result.= whatNum($curNum, "u", 0, "millones") . " ";	
				}
			}
		}

		// MILES

		// Centena de Mil
		if ($type == "cm")
		{
			$result .= whatNum($curNum, "c", $nextNum, "mil") . " ";
		}

		// Decena de Mil
		if ($type == "dm")
		{
			$result .= whatNum($curNum, "d", $desc[$i-1][1], "mil") . " ";
			if ($curNum == 1)
			{
				$dm10 = true;
			}
		}

		// Unidad de Mil
		if ($type == "um")
		{
			if (!$dm10)
			{
				$result.= whatNum($curNum, "u", 0, "mil") . " ";	
			}
		}

		// UNIDADES

		// Centena
		if ($type == "c")
		{
			$result .= whatNum($curNum, "c", $nextNum, "") . " ";
		}

		// Decena
		if ($type == "d")
		{
			$result .= whatNum($curNum, "d", $desc[$i-1][1], "") . " ";
			if ($curNum == 1)
			{
				$d10 = true;
			}
		}

		// Unidad
		if ($type == "u")
		{
			if (!$d10)
			{
				$result.= whatNum($curNum, "u", 0, "") . " ";	
			}
		}
	}
	//print("</div>");

	//print("<div>");
	//print("RESULT: ". strtoupper($result));
	//print("</div>");
	return $result;

}

?>