<?php
/* Copyright (C) 2003		Rodolphe Quiedeville		<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010	Laurent Destailleur			<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012	Regis Houssin				<regis.houssin@inodbox.com>
 * Copyright (C) 2008		Raphael Bertrand (Resultic)	<raphael.bertrand@resultic.fr>
 * Copyright (C) 2011		Fabrice CHERRIER
 * Copyright (C) 2013-2020  Philippe Grand	            <philippe.grand@atoo-net.com>
 * Copyright (C) 2015       Marcos García               <marcosgdf@gmail.com>
 * Copyright (C) 2018-2020  Frédéric France             <frederic.france@netlogic.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 * or see https://www.gnu.org/
 */

/**
 *	\file       htdocs/core/modules/contract/doc/pdf_ctsukasa.modules.php
 *	\ingroup    ficheinter
 *	\brief      Strato contracts template class file
 */
require_once DOL_DOCUMENT_ROOT.'/core/modules/contract/modules_contract.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';

require_once 'testnum.php';


/**
 *	Class to build contracts documents with model Strato
 */
class pdf_ctsukasa extends ModelePDFContract
{
	/**
	 * @var DoliDb Database handler
	 */
	public $db;

	/**
	 * @var string model name
	 */
	public $name;

	/**
	 * @var string model description (short text)
	 */
	public $description;

	/**
	 * @var int     Save the name of generated file as the main doc when generating a doc with this template
	 */
	public $update_main_doc_field;

	/**
	 * @var string document type
	 */
	public $type;

	/**
	 * @var array Minimum version of PHP required by module.
	 * e.g.: PHP ≥ 5.6 = array(5, 6)
	 */
	public $phpmin = array(5, 6);

	/**
	 * Dolibarr version of the loaded document
	 * @var string
	 */
	public $version = 'dolibarr';

	/**
	 * @var int page_largeur
	 */
	public $page_largeur;

	/**
	 * @var int page_hauteur
	 */
	public $page_hauteur;

	/**
	 * @var array format
	 */
	public $format;

	/**
	 * @var int marge_gauche
	 */
	public $marge_gauche;

	/**
	 * @var int marge_droite
	 */
	public $marge_droite;

	/**
	 * @var int marge_haute
	 */
	public $marge_haute;

	/**
	 * @var int marge_basse
	 */
	public $marge_basse;

	/**
	 * Issuer
	 * @var Societe
	 */
	public $emetteur;

	/**
	 * Recipient
	 * @var Societe
	 */
	public $recipient;

	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		global $conf, $langs, $mysoc;

		$this->db = $db;
		$this->name = 'ctsukasa';
		$this->description = $langs->trans("StandardContractsTemplate");
		$this->update_main_doc_field = 1; // Save the name of generated file as the main doc when generating a doc with this template

		// Page size for A4 format
		$this->type = 'pdf';
		$formatarray = pdf_getFormat();

		$this->page_largeur = $formatarray['width'];
		$this->page_hauteur = $formatarray['height'];
		$this->format = array($this->page_largeur, $this->page_hauteur);
		$this->marge_gauche = isset($conf->global->MAIN_PDF_MARGIN_LEFT) ? $conf->global->MAIN_PDF_MARGIN_LEFT : 10;
		$this->marge_droite = isset($conf->global->MAIN_PDF_MARGIN_RIGHT) ? $conf->global->MAIN_PDF_MARGIN_RIGHT : 10;
		$this->marge_haute = isset($conf->global->MAIN_PDF_MARGIN_TOP) ? $conf->global->MAIN_PDF_MARGIN_TOP : 10;
		$this->marge_basse = isset($conf->global->MAIN_PDF_MARGIN_BOTTOM) ? $conf->global->MAIN_PDF_MARGIN_BOTTOM : 10;

		$this->option_logo = 1; // Display logo
		$this->option_tva = 0; // Manage the vat option FACTURE_TVAOPTION
		$this->option_modereg = 0; // Display payment mode
		$this->option_condreg = 0; // Display payment terms
		$this->option_codeproduitservice = 0; // Display product-service code
		$this->option_multilang = 0; // Available in several languages
		$this->option_draft_watermark = 1; // Support add of a watermark on drafts

		// Get source company
		$this->emetteur = $mysoc;
		if (empty($this->emetteur->country_code)) {
			$this->emetteur->country_code = substr($langs->defaultlang, -2); // By default, if not defined
		}

		// Define position of columns
		$this->posxdesc = $this->marge_gauche + 1;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Function to build pdf onto disk
	 *
	 *  @param		Contrat			$object				Object to generate
	 *  @param		Translate		$outputlangs		Lang output object
	 *  @param		string			$srctemplatepath	Full path of source filename for generator using a template file
	 *  @param		int				$hidedetails		Do not show line details
	 *  @param		int				$hidedesc			Do not show desc
	 *  @param		int				$hideref			Do not show ref
	 *  @return		int									1=OK, 0=KO
	 */
	public function write_file($object, $outputlangs, $srctemplatepath = '', $hidedetails = 0, $hidedesc = 0, $hideref = 0)
	{
		// phpcs:enable
		global $user, $langs, $conf, $hookmanager, $mysoc;

		if (!is_object($outputlangs)) {
			$outputlangs = $langs;
		}
		// For backward compatibility with FPDF, force output charset to ISO, because FPDF expect text to be encoded in ISO
		if (!empty($conf->global->MAIN_USE_FPDF)) {
			$outputlangs->charset_output = 'ISO-8859-1';
		}

		// Load traductions files required by page
		$outputlangs->loadLangs(array("main", "dict", "companies", "contracts"));

		if ($conf->contrat->dir_output) {
			$object->fetch_thirdparty();

			// Definition of $dir and $file
			if ($object->specimen) {
				$dir = $conf->contrat->dir_output;
				$file = $dir."/SPECIMEN.pdf";
			} else {
				$objectref = dol_sanitizeFileName($object->ref);
				$dir = $conf->contrat->multidir_output[$object->entity]."/".$objectref;
				$file = $dir."/".$objectref.".pdf";
			}

			if (!file_exists($dir)) {
				if (dol_mkdir($dir) < 0) {
					$this->error = $outputlangs->trans("ErrorCanNotCreateDir", $dir);
					return 0;
				}
			}

			if (file_exists($dir)) {
				// Add pdfgeneration hook
				if (!is_object($hookmanager)) {
					include_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
					$hookmanager = new HookManager($this->db);
				}
				$hookmanager->initHooks(array('pdfgeneration'));
				$parameters = array('file'=>$file, 'object'=>$object, 'outputlangs'=>$outputlangs);
				global $action;
				$reshook = $hookmanager->executeHooks('beforePDFCreation', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks

				$pdf = pdf_getInstance($this->format);
				$default_font_size = pdf_getPDFFontSize($outputlangs); // Must be after pdf_getInstance
				$heightforinfotot = 50; // Height reserved to output the info and total part
				$heightforfreetext = (isset($conf->global->MAIN_PDF_FREETEXT_HEIGHT) ? $conf->global->MAIN_PDF_FREETEXT_HEIGHT : 5); // Height reserved to output the free text on last page
				$heightforfooter = $this->marge_basse + 8; // Height reserved to output the footer (value include bottom margin)
				if (!empty($conf->global->MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS)) {
					$heightforfooter += 6;
				}
				$pdf->SetAutoPageBreak(1, 0);

				if (class_exists('TCPDF')) {
					$pdf->setPrintHeader(false);
					$pdf->setPrintFooter(false);
				}
				$pdf->SetFont(pdf_getPDFFont($outputlangs));
				// Set path to the background PDF File
				if (!empty($conf->global->MAIN_ADD_PDF_BACKGROUND)) {
					$pagecount = $pdf->setSourceFile($conf->mycompany->dir_output.'/'.$conf->global->MAIN_ADD_PDF_BACKGROUND);
					$tplidx = $pdf->importPage(1);
				}

				$pdf->Open();
				$pagenb = 0;
				$pdf->SetDrawColor(128, 128, 128);

				$pdf->SetTitle($outputlangs->convToOutputCharset($object->ref));
				$pdf->SetSubject($outputlangs->transnoentities("ContractCard"));
				$pdf->SetCreator("Dolibarr ".DOL_VERSION);
				$pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
				$pdf->SetKeyWords($outputlangs->convToOutputCharset($object->ref)." ".$outputlangs->transnoentities("ContractCard")." ".$outputlangs->convToOutputCharset($object->thirdparty->name));
				if (!empty($conf->global->MAIN_DISABLE_PDF_COMPRESSION)) {
					$pdf->SetCompression(false);
				}

				$pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite); // Left, Top, Right

				// New page
				$pdf->AddPage();
				if (!empty($tplidx)) {
					$pdf->useTemplate($tplidx);
				}
				$pagenb++;
				$this->_pagehead($pdf, $object, 1, $outputlangs);
				$pdf->SetFont('', '', $default_font_size - 1);
				$pdf->MultiCell(0, 3, ''); // Set interline to 3
				$pdf->SetTextColor(0, 0, 0);

				$tab_top = 90;
				$tab_top_newpage = (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD) ? 42 : 10);

				// Display notes
				if (!empty($object->note_public)) {
					$tab_top -= 2;

					$pdf->SetFont('', '', $default_font_size - 1);
					$pdf->writeHTMLCell(190, 3, $this->posxdesc - 1, $tab_top - 1, dol_htmlentitiesbr($object->note_public), 0, 1);
					$nexY = $pdf->GetY();
					$height_note = $nexY - $tab_top;

					// Rect takes a length in 3rd parameter
					$pdf->SetDrawColor(192, 192, 192);
					$pdf->Rect($this->marge_gauche, $tab_top - 1, $this->page_largeur - $this->marge_gauche - $this->marge_droite, $height_note + 1);

					$tab_top = $nexY + 6;
				}

				$iniY = $tab_top + 7;
				$curY = $tab_top + 7;
				$nexY = $tab_top + 2;

				$pdf->SetXY($this->marge_gauche, $tab_top);

				//$pdf->MultiCell(0, 2, ''); // Set interline to 3. Then writeMultiCell must use 3 also.

				//$nblines = count($object->lines);

				// Loop on each lines
				for ($i = 0; $i < $nblines; $i++) {
					$objectligne = $object->lines[$i];

					$valide = $objectligne->id ? 1 : 0;

					if ($valide > 0 || $object->specimen) {
						$curX = $this->posxdesc - 1;
						$curY = $nexY;
						$pdf->SetFont('', '', $default_font_size - 1); // Into loop to work with multipage
						$pdf->SetTextColor(0, 0, 0);

						$pdf->setTopMargin($tab_top_newpage);
						$pdf->setPageOrientation('', 1, $heightforfooter + $heightforfreetext + $heightforinfotot); // The only function to edit the bottom margin of current page to set it.
						$pageposbefore = $pdf->getPage();

						// Description of product line

						if ($objectligne->date_ouverture_prevue) {
							$datei = dol_print_date($objectligne->date_ouverture_prevue, 'day', false, $outputlangs, true);
						} else {
							$datei = $langs->trans("Unknown");
						}

						if ($objectligne->date_fin_validite) {
							$durationi = convertSecondToTime($objectligne->date_fin_validite - $objectligne->date_ouverture_prevue, 'allwithouthour');
							$datee = dol_print_date($objectligne->date_fin_validite, 'day', false, $outputlangs, true);
						} else {
							$durationi = $langs->trans("Unknown");
							$datee = $langs->trans("Unknown");
						}

						if ($objectligne->date_ouverture) {
							$daters = dol_print_date($objectligne->date_ouverture, 'day', false, $outputlangs, true);
						} else {
							$daters = $langs->trans("Unknown");
						}

						if ($objectligne->date_cloture) {
							$datere = dol_print_date($objectligne->date_cloture, 'day', false, $outputlangs, true);
						} else {
							$datere = $langs->trans("Unknown");
						}

						$txtpredefinedservice = $objectligne->product_ref;
						if ($objectligne->product_label) {
							$txtpredefinedservice .= ' - ';
							$txtpredefinedservice .= $objectligne->product_label;
						}

						$desc = dol_htmlentitiesbr($objectligne->desc, 1); // Desc (not empty for free lines)
						$txt = '';
						$txt .= $outputlangs->transnoentities("Quantity").' : <strong>'.$objectligne->qty.'</strong> - '.$outputlangs->transnoentities("UnitPrice").' : <strong>'.price($objectligne->subprice).'</strong>'; // Desc (not empty for free lines)
						if (empty($conf->global->CONTRACT_HIDE_PLANNED_DATE_ON_PDF)) {
							$txt .= '<br>';
							$txt .= $outputlangs->transnoentities("DateStartPlannedShort")." : <strong>".$datei."</strong> - ".$outputlangs->transnoentities("DateEndPlanned")." : <strong>".$datee.'</strong>';
						}
						if (empty($conf->global->CONTRACT_HIDE_REAL_DATE_ON_PDF)) {
							$txt .= '<br>';
							$txt .= $outputlangs->transnoentities("DateStartRealShort")." : <strong>".$daters.'</strong>';
							if ($objectligne->date_cloture) {
								$txt .= " - ".$outputlangs->transnoentities("DateEndRealShort")." : '<strong>'".$datere.'</strong>';
							}
						}

						$pdf->startTransaction();
						//$pdf->writeHTMLCell(0, 0, $curX, $curY, dol_concatdesc($txtpredefinedservice, dol_concatdesc($txt, $desc)), 0, 1, 0);
						$pageposafter = $pdf->getPage();
						if ($pageposafter > $pageposbefore) {	// There is a pagebreak
							$pdf->rollbackTransaction(true);
							$pageposafter = $pageposbefore;
							//print $pageposafter.'-'.$pageposbefore;exit;
							$pdf->setPageOrientation('', 1, $heightforfooter); // The only function to edit the bottom margin of current page to set it.
							//$pdf->writeHTMLCell(0, 0, $curX, $curY, dol_concatdesc($txtpredefinedservice, dol_concatdesc($txt, $desc)), 0, 1, 0);
							$pageposafter = $pdf->getPage();
							$posyafter = $pdf->GetY();

							if ($posyafter > ($this->page_hauteur - ($heightforfooter + $heightforfreetext + $heightforinfotot))) {	// There is no space left for total+free text
								if ($i == ($nblines - 1)) {	// No more lines, and no space left to show total, so we create a new page
									$pdf->AddPage('', '', true);
									if (!empty($tplidx)) {
										$pdf->useTemplate($tplidx);
									}
									if (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)) {
										$this->_pagehead($pdf, $object, 0, $outputlangs);
									}
									$pdf->setPage($pageposafter + 1);
								}
							} else {
								// We found a page break

								// Allows data in the first page if description is long enough to break in multiples pages
								if (!empty($conf->global->MAIN_PDF_DATA_ON_FIRST_PAGE)) {
									$showpricebeforepagebreak = 1;
								} else {
									$showpricebeforepagebreak = 0;
								}
							}
						} else // No pagebreak
						{
							$pdf->commitTransaction();
						}

						$nexY = $pdf->GetY() + 2;
						$pageposafter = $pdf->getPage();

						$pdf->setPage($pageposbefore);
						$pdf->setTopMargin($this->marge_haute);
						$pdf->setPageOrientation('', 1, 0); // The only function to edit the bottom margin of current page to set it.

						// We suppose that a too long description is moved completely on next page
						if ($pageposafter > $pageposbefore) {
							$pdf->setPage($pageposafter);
							$curY = $tab_top_newpage;
						}

						$pdf->SetFont('', '', $default_font_size - 1); // We reposition the default font

						// Detect if some page were added automatically and output _tableau for past pages
						while ($pagenb < $pageposafter) {
							$pdf->setPage($pagenb);
							if ($pagenb == 1) {
								$this->_tableau($object, $pdf, $tab_top, $this->page_hauteur - $tab_top - $heightforfooter - $heightforfreetext, 0, $outputlangs, 0, 1);
							} else {
								$this->_tableau($object, $pdf, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforfooter - $heightforfreetext, 0, $outputlangs, 1, 1);
							}
							$this->_pagefoot($pdf, $object, $outputlangs, 1);
							$pagenb++;
							$pdf->setPage($pagenb);
							$pdf->setPageOrientation('', 1, 0); // The only function to edit the bottom margin of current page to set it.
						}

						if (isset($object->lines[$i + 1]->pagebreak) && $object->lines[$i + 1]->pagebreak) {
							if ($pagenb == 1) {
								$this->_tableau($object, $pdf, $tab_top, $this->page_hauteur - $tab_top - $heightforfooter - $heightforfreetext, 0, $outputlangs, 0, 1);
							} else {
								$this->_tableau($object, $pdf, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforfooter - $heightforfreetext, 0, $outputlangs, 1, 1);
							}
							$this->_pagefoot($pdf, $object, $outputlangs, 1);
							// New page
							$pdf->AddPage();
							if (!empty($tplidx)) {
								$pdf->useTemplate($tplidx);
							}
							$pagenb++;
						}
					}
				}

				// Show square
				if ($pagenb == 1) {
					$this->_tableau($object, $pdf, $tab_top, $this->page_hauteur - $tab_top - $heightforinfotot - $heightforfreetext - $heightforfooter, 0, $outputlangs, 0, 0);
					//$this->tabSignature($pdf, $tab_top, $this->page_hauteur - $tab_top - $heightforinfotot - $heightforfreetext - $heightforfooter, $outputlangs);
					$bottomlasttab = $this->page_hauteur - $heightforfooter - $heightforfooter + 1;
				} else {
					$this->_tableau($object, $pdf, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforinfotot - $heightforfreetext - $heightforfooter, 0, $outputlangs, 0, 0);
					//$this->tabSignature($pdf, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforinfotot - $heightforfreetext - $heightforfooter, $outputlangs);
					$bottomlasttab = $this->page_hauteur - $heightforfooter - $heightforfooter + 1;
				}

				$this->_pagefoot($pdf, $object, $outputlangs);
				if (method_exists($pdf, 'AliasNbPages')) {
					$pdf->AliasNbPages();
				}

				$pdf->Close();

				$pdf->Output($file, 'F');

				// Add pdfgeneration hook
				if (!is_object($hookmanager)) {
					include_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
					$hookmanager = new HookManager($this->db);
				}
				$hookmanager->initHooks(array('pdfgeneration'));
				$parameters = array('file'=>$file, 'object'=>$object, 'outputlangs'=>$outputlangs);
				global $action;
				$reshook = $hookmanager->executeHooks('afterPDFCreation', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
				if ($reshook < 0) {
					$this->error = $hookmanager->error;
					$this->errors = $hookmanager->errors;
				}

				if (!empty($conf->global->MAIN_UMASK)) {
					@chmod($file, octdec($conf->global->MAIN_UMASK));
				}

				$this->result = array('fullpath'=>$file);

				return 1;
			} else {
				$this->error = $langs->trans("ErrorCanNotCreateDir", $dir);
				return 0;
			}
		} else {
			$this->error = $langs->trans("ErrorConstantNotDefined", "CONTRACT_OUTPUTDIR");
			return 0;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 *   Show table for lines
	 *
	 *   @param		Contrat		$object			Contract
	 * 	 @param		TCPDF		$pdf     		Object PDF
	 *   @param		string		$tab_top		Top position of table
	 *   @param		string		$tab_height		Height of table (rectangle)
	 *   @param		int			$nexY			Y
	 *   @param		Translate	$outputlangs	Langs object
	 *   @param		int			$hidetop		Hide top bar of array
	 *   @param		int			$hidebottom		Hide bottom bar of array
	 *   @return	void
	 */
	protected function _tableau($object, &$pdf, $tab_top, $tab_height, $nexY, $outputlangs, $hidetop = 0, $hidebottom = 0)
	{
		$pdf->SetLeftMargin(25);
		$pdf->SetRightMargin(25);
		$pdf->SetTopMargin(25);
		$pdf->SetAutoPageBreak(true, 25);
		$pdf->SetXY(25,25);
		$pdf->SetFont('','',10);

		//$pdf->WriteHTML($object->id);

		function getMonth($month)
		{
			switch($month)
			{
				case 1:
					return "Enero";
					break;
				case 2:
					return "Febrero";
					break;
				case 3:
					return "Marzo";
					break;
				case 4:
					return "Abril";
					break;
				case 5:
					return "Mayo";
					break;
				case 6:
					return "Junio";
					break;
				case 7:
					return "Julio";
					break;
				case 8:
					return "Agosto";
					break;
				case 9:
					return "Septiembre";
					break;
				case 10:
					return "Octubre";
					break;
				case 11:
					return "Noviembre";
					break;
				case 12:
					return "Diciembre";
					break;
			}
		}

		function getDay($day)
		{
			switch($day)
			{
				case "Monday":
					return "Lunes";
					break;
				case "Tuesday":
					return "Martes";
					break;
				case "Wednesday":
					return "Miércoles";
					break;
				case "Thursday":
					return "Jueves";
					break;
				case "Friday":
					return "Viernes";
					break;
				case "Saturday":
					return "Sábado";
					break;
				case "Sunday":
					return "Domingo";
					break;			
			}
		}

		function getHonorario($honor)
		{
			switch($honor)
			{
				case "MR":
					return "el Señor";
					break;
				case "MME":
					return "la Señora";
					break;
			}
		}

		$arrendatario = false;
		$solidario1 = false;
		$solidario2 = false;
		$solidario3 = false;

		// Obtiene la información de los contactos a partir del contrato actual
		$contacts = $object->liste_contact(-1);
		//$pdf->WriteHTML(count($contacts));

		// Consulta dentro de la tabla llx_c_type_contact la información del contacto
		foreach ($contacts as $ct){
			//$pdf->WriteHTML($ct['libelle']);
			$type = $ct['libelle'];
			
			$cont = new Contact($this->db);
			$cont->fetch($ct['id']);
			//$pdf->WriteHTML($cont->town);
			$ct['town'] = $cont->town;
			$ct['cc'] = $cont->array_options['options_contacto_cedula'];
			$ct['townCC'] = $cont->array_options['options_contacto_cedula_expedicion'];
			$ct['cel'] = $cont->phone_mobile;
			//$pdf->WriteHTML("cel: ". $cont->phone_mobile);

			if ($type == "Arrendatario"){
				$arrendatario = $ct;
			}
			if ($type == "Primer Deudor Solidario"){
				$solidario1 = $ct;
			}
			if ($type == "Segundo Deudor Solidario"){
				$solidario2 = $ct;
			}
			if ($type == "Tercer Deudor Solidario"){
				$solidario3 = $ct;
			}			
		}
		
		/*
		 *	INFORMACIÓN ARRENDATARIO
		 */

		$arrendatario_nombre;		
		$arrendatario_ciudad;		
		$arrendatario_honorario;
		$arrendatario_cc;			
		$arrendatario_cc_expedicion;
		$arrendatario_correo;
		$arrendatario_celular;

		//$pdf->WriteHTML("<br><div>INFORMACIÓN ARRENDATARIO");

		if ($arrendatario){
			$arrendatario_nombre = $arrendatario['firstname'] ." ". $arrendatario['lastname'];
			$arrendatario_ciudad = $arrendatario['town'];
			$arrendatario_honorario = getHonorario($arrendatario['civility']);
			$arrendatario_cc = $arrendatario['cc'];
			$arrendatario_cc_expedicion = $arrendatario['townCC'];
			$arrendatario_correo = $arrendatario['email'];
			$arrendatario_celular = $arrendatario['cel'];
			
			/* $pdf->WriteHTML($arrendatario_nombre);
			$pdf->WriteHTML($arrendatario_ciudad);
			$pdf->WriteHTML($arrendatario_cc);
			$pdf->WriteHTML($arrendatario_cc_expedicion);
			$pdf->WriteHTML($arrendatario_correo);
			$pdf->WriteHTML($arrendatario_celular); */
		}

		//$pdf->WriteHTML("</div>");

		/*
		 *	INFORMACIÓN SOLIDARIO 1
		 */

		$solidario1_nombre;
		$solidario1_ciudad;
		$solidario1_honorario;
		$solidario1_cc;
		$solidario1_cc_expedicion;
		$solidario1_correo;
		$solidario1_celular;

		//$pdf->WriteHTML("<br><div>INFORMACIÓN PRIMER DEUDOR SOLIDARIO");

		if ($solidario1){
			$solidario1_nombre = $solidario1['firstname'] ." ". $solidario1['lastname'];
			$solidario1_ciudad = $solidario1['town'];
			$solidario1_honorario = getHonorario($solidario1['civility']);
			$solidario1_cc = $solidario1['cc'];
			$solidario1_cc_expedicion = $solidario1['townCC'];
			$solidario1_correo = $solidario1['email'];
			$solidario1_celular = $solidario1['cel'];
			
			/* $pdf->WriteHTML($solidario1_nombre);
			$pdf->WriteHTML($solidario1_ciudad);
			$pdf->WriteHTML($solidario1_cc);
			$pdf->WriteHTML($solidario1_cc_expedicion);
			$pdf->WriteHTML($solidario1_correo);
			$pdf->WriteHTML($solidario1_celular); */
		}

		//$pdf->WriteHTML("</div>");

		/*
		 *	INFORMACIÓN SOLIDARIO 2
		 */

		$solidario2_nombre;
		$solidario2_ciudad;
		$solidario2_honorario;
		$solidario2_cc;
		$solidario2_cc_expedicion;
		$solidario2_correo;
		$solidario2_celular;

		//$pdf->WriteHTML("<br><div>INFORMACIÓN SEGUNDO DEUDOR SOLIDARIO");

		if ($solidario2){
			$solidario2_nombre = $solidario2['firstname'] ." ". $solidario2['lastname'];
			$solidario2_ciudad = $solidario2['town'];
			$solidario2_honorario = getHonorario($solidario2['civility']);
			$solidario2_cc = $solidario2['cc'];
			$solidario2_cc_expedicion = $solidario2['townCC'];
			$solidario2_correo = $solidario2['email'];
			$solidario2_celular = $solidario2['cel'];
			
			/* $pdf->WriteHTML($solidario2_nombre);
			$pdf->WriteHTML($solidario2_ciudad);
			$pdf->WriteHTML($solidario2_cc);
			$pdf->WriteHTML($solidario2_cc_expedicion);
			$pdf->WriteHTML($solidario2_correo);
			$pdf->WriteHTML($solidario2_celular); */
		}

		//$pdf->WriteHTML("</div>");


		/*
		 *	INFORMACIÓN SOLIDARIO 3
		 */

		$solidario3_nombre;
		$solidario3_ciudad;
		$solidario3_honorario;
		$solidario3_cc;
		$solidario3_cc_expedicion;
		$solidario3_correo;
		$solidario3_celular;

		//$pdf->WriteHTML("<br><div>INFORMACIÓN TERCER DEUDOR SOLIDARIO");

		if ($solidario3){
			$solidario3_nombre = $solidario3['firstname'] ." ". $solidario3['lastname'];
			$solidario3_ciudad = $solidario3['town'];
			$solidario3_honorario = getHonorario($solidario3['civility']);
			$solidario3_cc = $solidario3['cc'];
			$solidario3_cc_expedicion = $solidario3['townCC'];
			$solidario3_correo = $solidario3['email'];
			$solidario3_celular = $solidario3['cel'];
			
			/* $pdf->WriteHTML($solidario3_nombre);
			$pdf->WriteHTML($solidario3_ciudad);
			$pdf->WriteHTML($solidario3_cc);
			$pdf->WriteHTML($solidario3_cc_expedicion);
			$pdf->WriteHTML($solidario3_correo);
			$pdf->WriteHTML($solidario3_celular); */
		}

		//$pdf->WriteHTML("</div>");

		/*
		 *	INFORMACIÓN INMUEBLE
		 */

		$object->fetch_project();		 
		$project = $object->project;
		
		$inmueble_direccion = $project->array_options['options_proyecto_inmueble_direccion'];
		$inmueble_matricula = $project->array_options['options_proyecto_inmueble_matricula'];
		$inmueble_ficha_catastral = $project->array_options['options_proyecto_inmueble_ficha_catastral'];
		
		/* $pdf->WriteHTML("<br><div>INFORMACIÓN INMUEBLE");

		$pdf->WriteHTML("projectid: ". $project->id);

		$pdf->WriteHTML("inmDir: ". $inmueble_direccion);
		$pdf->WriteHTML("inmMat: ". $inmueble_matricula);
		$pdf->WriteHTML("inmFic: ". $inmueble_ficha_catastral);

		$pdf->WriteHTML("</div>"); */

		/*
		 *	INFORMACIÓN ARRENDAMIENTO
		 */

		$arrendamiento_precio = "$". price($object->lines[0]->subprice);
		$arrendamiento_precio_num = explode(".", $object->lines[0]->subprice)[0];
		$arrendamiento_precio_text = strtoupper(numToText($arrendamiento_precio_num));
		//$pdf->WriteHTML($arrendamiento_precio_text);
		$arrendamiento_cuota_admin = "$". price($object->array_options['options_contrato_cuota_admin']);
		$arrendamiento_cuota_admin_num = explode(".", $object->array_options['options_contrato_cuota_admin'])[0];
		$arrendamiento_cuota_admin_text = strtoupper(numToText($arrendamiento_cuota_admin_num));
		$arrendamiento_plazo_pago = $object->array_options['options_contrato_plazo_pago'];

		/*
		 *	INFORMACIÓN CONTRATO 
		 */

		$contrato_duracion = $object->array_options['options_contrato_duracion'];

		// Fecha de inicio
		$contrato_inicio_dia = date("d", $object->date_contrat);
		$contrato_inicio_diaS = getDay(date("l", $object->date_contrat));
		$contrato_inicio_mes = getMonth(date("m", $object->date_contrat));
		$contrato_inicio_year = date("Y", $object->date_contrat);
		
		// Fecha de finalización
		$contrato_fin_dia = date("d",$object->array_options['options_contrato_fin']);
		$contrato_fin_diaS = getDay(date("d",$object->array_options['options_contrato_fin']));
		$contrato_fin_mes = getMonth(date("m",$object->array_options['options_contrato_fin']));
		$contrato_fin_year = date("Y",$object->array_options['options_contrato_fin']);

		$contrato_ciudad = strtoupper($object->array_options['options_contrato_ciudad']);

		// Fecha de firma
		$contrato_fecha_firma_dia = date("d", $object->array_options['options_contrato_firma']);
		$contrato_fecha_firma_diaS = getDay(date("l", $object->array_options['options_contrato_firma']));
		$contrato_fecha_firma_mes = getMonth(date("m", $object->array_options['options_contrato_firma']));
		$contrato_fecha_firma_year = date("Y", $object->array_options['options_contrato_firma']);

		/*
		 *	IMPRESIÓN 
		 */

		/* $pdf->WriteHTML("arr: $arrendamiento_precio<br>");
		
		$pdf->WriteHTML("arradmin: $arrendamiento_cuota_admin<br>");
		
		$pdf->WriteHTML("arrpl: $arrendamiento_plazo_pago<br>");
		
		$pdf->WriteHTML("<div>");
		$pdf->WriteHTML("cd: $contrato_duracion<br>");

		$pdf->WriteHTML("ci: con fecha de iniciación el día $contrato_inicio_dia $contrato_inicio_mes del año $contrato_inicio_year<br>");

		$pdf->WriteHTML("cf: y cuya fecha de terminación es el día $contrato_fin_dia de $contrato_fin_mes del año $contrato_fin_year.<br>");

		$pdf->WriteHTML("cdd: $contrato_ciudad<br>");

		$pdf->WriteHTML("cff: firman en la ciudad de $contrato_ciudad, a los $contrato_fecha_firma_dia días del mes de $contrato_fecha_firma_mes del año $contrato_fecha_firma_year<br>");
		$pdf->WriteHTML("</div>"); */

		$texto = '<div align="justify">';

		$texto .= "<br><br><br><br><br>";

		$texto .= '<span align="center">
		GRUPO SUKASA S.A.S.<br>
		NIT 900753888-9<br>
		Dirección: Calle 8 B N° 15-33 Ed. Portal de los Alpes Local 204 Pereira R/da<br>
		Sector Circunvalar<br>
		E-mail: servicioalcliente@gruposukasa.com<br><br><br>
		</span>';

		$texto .= "Conste por medio del presente documento, que entre los suscritos, a saber: de una parte, <b>ALEJANDRA CATALINA BARROS IBAÑEZ</b> identificada con la cédula de ciudadanía No. 42.132.379 expedida en la ciudad de Pereira Risaralda, actuando en representación de la sociedad <b>GRUPO SUKASA S.A.S.</b> identificada con el Nit: 900.753.888-9, persona jurídica ésta, constituida mediante documento privado No. 001 del 21 de julio de 2014, con domicilio principal en la ciudad de Pereira Risaralda, debidamente registrada en la Cámara de Comercio de esa misma ciudad el día 28 de Julio de 2014, bajo el número 1033443, Matricula Mercantil No. 18116679, lo cual acredita con el certificado de existencia y representación legal, quien para los efectos del presente contrato se denominara el <b>ARRENDADOR</b>, ";
		
		$texto .= "y de la otra <b>". $arrendatario_nombre ."</b>, persona mayor de edad, vecino de la ciudad de ". $arrendatario_ciudad .", identificado con la C.C ". $arrendatario_cc .".408 expedida en <b>". $arrendatario_cc_expedicion ."</b> el primero de los citados, quien actuará como ARRENDATARIO, ";
		
		if ($solidario1){
			$texto .= "y de la otra ". $solidario1_honorario ." ". $solidario1_nombre ." identificada con Cedula de ciudadanía No ". $solidario1_cc." expedida en ". $solidario1_cc_expedicion .", persona mayor de edad y vecina de la ciudad de ". $solidario1_ciudad .", el segundo de los citados, quien actuará como PRIMER DEUDOR SOLIDARIO, ";
			
			if ($solidario2){
				$texto.= "y de la otra ". $solidario2_honorario ." ". $solidario2_nombre ." identificada con Cedula de ciudadanía No ". $solidario2_cc ." expedida en ". $solidario2_cc_expedicion .", persona mayor de edad y vecina de la ciudad de ". $solidario2_ciudad .", el tercero de los citados, quien actuará como SEGUNDO DEUDOR SOLIDARIO, ";
				
				if ($solidario3){
					$texto.= "y de la otra ". $solidario3_honorario ." ". $solidario3_nombre ." identificada con Cedula de ciudadanía No ". $solidario3_cc ." expedida en ". $solidario3_cc_expedicion .", persona mayor de edad y vecina de la ciudad de ". $solidario3_ciudad .", el cuarto de los citados, quien actuará como TERCER DEUDOR SOLIDARIO, ";
				}
			}
		}
		$texto .= "quienes son consecuentes solidarios con las obligaciones y derechos que emergen de lo estipulado a este documento, celebrando el presente contrato de arrendamiento de vivienda:";
		
		$texto .= "<br>"."<b>PARAGRAFO 1.</b> Se aclara que desde ya el arrendatario acepta y autoriza al arrendador o a la persona que esta ceda sus derechos para que, en anexo separado completamente, anexe los linderos generales o específicos del o de los inmuebles que recibe en arrendamiento si fuera necesario, así mismo declaran conocerlos y aceptan que estos figuren en escrito aparte, anexo al presente contrato, junto con los demás elementos en inventario separado firmando por las partes";
		
		$texto .= "<br>"."<b>PARAGRAFO 2 - DESTINACIÓN</b>, El inmueble será destinado exclusivamente para vivienda del arrendatario y de su familia, destinación que no podrá ser cambiada por EL ARRENDATARIO sin autorización escrita y expresa del ARRENDADOR, de darse, será causal para dar por terminado el contrato de arrendamiento y exigir la entrega material e inmediata del inmueble arrendado, así como la correspondiente indemnización de perjuicios.";
		
		$texto .= "<br>"."<b>PARAGRAFO 3</b> AL ARRENDATARIO le está prohibido destinar el inmueble a fines ilícitos utilizarlo para ocultar o depositar armas, explosivos, actividades de secuestro o depósito de dineros de procedencia ilícito o de almacenes de depósito, artículos de contrabando o para que en él se elaboren, almacenen o vendan drogas estupefacientes o sustancias alucinógenas, u otra actividad que ponga en peligro el inmueble. Este contrato se regulará por las siguientes cláusulas especiales:";
		
		$texto .= "<br>"."<b>PRIMERA - OBJETO:</b> Mediante el presente contrato el Arrendador concede a título de arrendamiento al Arrendatario el goce del inmueble ubicado en ". $inmueble_direccion ."  con matrícula inmobiliaria ". $inmueble_matricula .", y ficha catastral No ". $inmueble_ficha_catastral ." de la ciudad de ". $inmueble_ciudad ." cuyos linderos y ubicación quedaron arriba determinados por medio de la ficha catastral.";
		
		$texto .= "<br>"."<b>SEGUNDA – PRECIO:</b>  El precio o canon mensual del arrendamiento es la suma de ". $arrendamiento_precio ." (". $arrendamiento_precio_text ." PESOS M/CTE) incluida la cuota de administración de la Propiedad Horizontal la cual se encuentra a la fecha en el valor de ". $arrendamiento_cuota_admin ." (". $arrendamiento_cuota_admin_text ." PESOS M/CTE), cuyo pago se hará en dinero efectivo en la ciudad de PEREIRA, en la Calle 8b 15 33 Local 204 Edificio Portal de Los Alpes Barrio los Alpes, o mediante consignación a la cuenta de ahorros Bancolombia No. 723-505503-06 a nombre de GRUPO SUKASA S.A.S, de manera anticipada, el pago deberá efectuarse dentro de los primeros ". $arrendamiento_plazo_pago ." días al inicio de cada periodo mensual.";
		
		$texto .= "<br>"."<b>PARAGRAFO PRIMERO: </b>Adjunto con el valor del canon de arrendamiento, el Arrendatario deberá cancelar lo correspondiente a Intereses moratorios que se causen y por el hecho de incurrirse en mora, así esta mora sea de un día, pero que, no obstante, y efectuado el pago de la mora en que se incurra, el Arrendatario queda o está inmerso en la causal de incumplimiento del contrato, ya que no opera a favor de este el fenómeno de la purga de la mora. PARAGRAFO SEGUNDO: El valor que se cause por concepto de Cuota de Administración del inmueble que esta reglado bajo el régimen de propiedad Horizontal, será de cargo del Arrendatario, esto es independiente del valor del canon de arrendamiento pactado los incrementos anuales en la cuota de administración serán ajenos al incremento del canon de arrendamiento, cuyo valor en últimas le corresponde al Arrendatario, POR SER UN INMUEBLE AMPARADO POR EL REGIMEN DE PROPIEDAD HORIZONTAL TAL COBRO SE GENERARÁ PARA EL PRESENTE CONTRATO. PARAGRAFO TERCERO: El canon de arrendamiento se incrementará anualmente en un valor correspondiente al IPC del año inmediatamente anterior, por cada vigencia del contrato";
		
		$texto .= "<br>"."<b>TERCERA – TERMINO:</b>  El término de duración del contrato es por ". $contrato_duracion ." meses, con fecha de iniciación el día ". $contrato_inicio_dia ." de ". $contrato_inicio_mes ." del año ". $contrato_inicio_year." y cuya fecha de terminación es el día ". $contrato_fin_dia." de ". $contrato_fin_mes." del año ". $contrato_fin_year .".";
		
		$texto .= "<br>"."<b>CUARTA - SERVICIOS:</b> El Inmueble de vivienda dado en arrendamiento tiene instalados y en normal uso los siguientes servicios públicos de Acueducto, Alcantarillado, Energía Eléctrica, gas, cuyos consumos y desde la fecha de la entrega corresponden o son de cargo en su pago de los Arrendatarios y hasta el día en que se dé real y efectivamente la entrega de lo arrendado al arrendador. PARAGRAFO: Tanto de parte del Arrendador y los Arrendatarios, se tomará lectura de Contadores de Agua y Energía, gas, tanto a la fecha de entrega a los Arrendatarios, como a la fecha de recibo de parte del Arrendador.";
		
		$texto .= "<br>"."<b>QUINTA - ENTREGA:</b> EL ARRENDATARIO declara que ha recibido el inmueble arrendado en buen estado y a conformidad con el inventario que, como anexo, hace parte integral del presente contrato. Se debe tener en cuenta que al momento de hacer la devolución del inmueble, este debe estar en las mismas condiciones en las que se entregó, con excepción del desgaste por uso, lo que quiere decir que en caso de modificaciones de color, perforaciones o cualquier otra modificación  esta se deberá volver al estado original de cuando fue entregado deberá realizarse la entrega con la pintura total del inmueble, se entiende que el inmueble se restituirá con el desgaste por uso , Así mismo declara que ha recibido el inmueble con todas sus instalaciones eléctricas, sanitarias, hidráulicas, gas, telefónicas y estructurales en perfecto estado de funcionamiento, todo lo cual consta en el referido anexo <b>PARAGRAFO: RENUNCIA A REQUERIMIENTOS EL ARRENDATARIO Y LOS DEUDORES SOLIDARIOS</b> que al final del contrato se citan renuncian expresamente a los requerimientos de que trata el Artículo 423 del CGP., y en general a los que consagre cualquier norma sustancial o procesal para efectos de la constitución en mora en el cumplimiento de alguna de las obligaciones adquiridas en virtud del presente.";
		
		$texto .= "<br>"."<b>SEXTA - INCREMENTO DEL PRECIO Y PRORROGAS.</b>  Vencido los ". $contrato_duracion ." meses de vigencia de este contrato y así sucesivamente cada ". $contrato_duracion ." meses, en caso de prórroga tácita o expresa, en forma automática y sin necesidad de requerimiento alguno entre las parles. el precio anual de la renta se incrementará en una proporción igual al tope máximo permitido por las disposiciones vigentes al momento que tenga lugar el reajuste, esto es en un 100% del tope establecido para el Índice de Precios al Consumidor (IPC) del año inmediatamente anterior. En caso de que EL ARRENDATARIO no desee prorrogar el contrato deberá notificar al ARRENDADOR no con menos de noventa (90) días antes del vencimiento del contrato, durante su término inicial o durante una de sus prórrogas. Si es voluntad de EL ARRENDADOR no prorrogar, dicha notificación se hará en los términos anteriores, no con menos de noventa (90) días antes del vencimiento.";
		
		$texto .= "<br>"."<b>SEPTIMA -OBLIGACIONES DEL ARRENDATARIO.</b>  Son obligaciones de EL ARRENDATARIO: 1) pagar dentro del plazo previsto para el efecto, el precio que se ha fijado para el arrendamiento, junto con cuotas de administración en caso de que haya lugar a las mismas, 2) abstenerse de usar el bien para fines distintos a los estipulados, 3) observar el reglamento de propiedad horizontal, en caso que exista el mismo 4) conservar el inmueble en el mismo estado en que lo recibió, salvo el deterioro que se derive de su uso normal, o natural. 5) hacer, a su costa, las reparaciones locativas que requiera el inmueble, 6) informar oportunamente a EL ARRENDADOR sobre la ocurrencia de daños que demanden la ejecución de reparaciones necesarias, y asumir las que se hayan hecho necesarias por su culpa, 7) abstenerse de adelantar mejoras o reformas cuando no medie autorización expresa y por escrito de EL ARRENDADOR para tal efecto, 8) pagar, oportunamente los servicios públicos y demás erogaciones a su cargo, 9) abstenerse de fijar avisos en el inmueble, 10) abstenerse de ceder el contrato de arrendamiento o de celebrar subarriendos sin que medie autorización expresa y por escrito de EL ARRENDADOR, 11) reconocer y pagar a EL ARRENDADOR intereses moratorios en caso de mora en el cumplimiento de las obligaciones dinerarias contraídas, 12) salvo su deterioro normal, restituir el inmueble a la terminación del contrato en las mismas condiciones que lo recibió, especialmente en lo referente al estado de su pintura general, en este caso el arrendatario responderá por daños materiales causados al inmueble e imputables a negligencia de este por lo cual este contrato presta merito ejecutivo para hacer exigible el pago de todo perjuicio. 13) las demás que se deriven del presente contrato o de la ley <b>PARAGRAFO 1.</b> Sin perjuicio de lo aquí dispuesto, algunas de las obligaciones de EL ARRENDATARIO son objeto de especial regulación en cláusulas posteriores, y por tanto aplicables.";
		
		$texto .= "<br>"."<b>OCTAVA - OBLIGACIONES DE EL ARRENDADOR.</b>  Son obligaciones de EL ARRENDADOR: 1) Entregar al ARRENDATARIO el bien inmueble arrendado, en buen estado que permita su uso legítimo conforme inventario que hace parte integral de este contrato. 2) Mantener dicho bien en estado de servir para e fin para el que ha sido arrendado y en consecuencia hacer, a su costa, las reparaciones necesarias que EL ARRENDATARIO le solicite ce forma oportuna. 3) De conformidad con la ley, librar a EL ARRENDATARIO de toda turbación o problema en el goce Del bien arrendado 4) Al momento de la terminación del contrato recibir de EL ARRENDATARIO o de la persona a quien se haya designado el inmueble conforme el inventario. 5) Las demás que se deriven del presente contrato o de la ley. PARÁGRAFO 1. Sn perjuicio de lo aquí dispuesto, algunas de las obligaciones de EL ARRENDADOR son objeto de especial regulación en clausulas posteriores, y por tanto aplicables.";
		
		$texto .= "<br>"."<b>NOVENA - LÍNEA TELEFÓNICA.</b>  Se deja expresa constancia que el inmueble se arrienda SIN LINEA TELEFONICA. Si durante la vigencia de este contrato se instalare en el inmueble arrendado una o más líneas telefónicas EL ARRENDATARIO se compromete al pago del servicio telefónico correspondiente a esta, colocada a su nombre- EL ARRENDATARIO se obliga a pagar de forma oportuna los cobros que se generen por el uso del servicio de telefonía, TV, internet y cualquier otro que de esta se derive. De la misma manera restituirán el inmueble libre de este servicio.";
		
		$texto .= "<br>"."<b>DECIMA - REPARACIONES NECESARIAS.</b>  Las reparaciones necesarias o indispensables serán de cargo de EL ARRENDADOR. EL ARRENDATARIO deberá avisar a EL ARRENDADOR, de forma oportuna y mediante comunicación escrita, de la ocurrencia del daño que origina la necesidad de reparación. En dicha comunicación EL ARRENDATARIO deberá indicar la naturaleza del daño y su gravedad. En caso de especial urgencia el aviso podrá darse inicialmente de manera verbal o telefónica, sin perjuicio que, posteriormente, se reitere por escrito. Una vez conocida la ocurrencia del daño y dentro de un término prudencial, EL ARRENDADOR deberá adelantar la correspondiente reparación. No serán de cargo de EL ARRENDADOR aquellas reparaciones que se hayan hecho necesarias por culpa de EL ARRENDATARIO, de sus dependientes, huéspedes o familiares. Se entiende por reparaciones necesarias aquellas indispensables para que la cosa pueda prestar su uso ordinario, así como aquellas sin las cuales la cosa podría perecer. El arrendatario renuncia expresamente a descontar de la renta el valor de las reparaciones indispensables a que se refiere el Artículo 27 de la Ley 820 de 2003.";

		$texto .= "<br>"."<b>DÉCIMA PRIMERA - MEJORAS Y REFORMAS.</b>   EL ARRENDATARIO no podrá hacer mejoras ni reformas el inmueble sin que previamente haya sido autorizado por escrito por EL ARRENDADOR. EL ARRENDADOR no estará obligado a pagar las mejoras y/o reformas que no haya autorizado. En todo caso EL ARRENDATARIO podrá retirar las mejoras y/o reformas introducidas en el inmueble, siempre y cuando se pueda proceder a ello sin detrimento del inmueble; en caso contrario, las mejoras quedarán de propiedad de EL ARRENDADOR, quien no estará obligado a reconocer, por tal concepto, suma alguna a favor de EL ARRENDATARIO.";
		
		$texto .= "<br>"."<b>DECIMA SEGUNDA - FIJACIÓN DE AVISOS.</b>  EL ARRENDATARIO no podrá fijar en los muros, puertas ventanas del inmueble avisos de ninguna naturaleza, sin autorización escrita de EL ARRENDADOR";
		
		$texto .= "<br>"."<b>DECIMA TERCERA - DEVOLUCION SATISFACTORIA.</b>  A la terminación del contrato, EL ARRENDATARIO deberá restituir el inmueble en las mismas condiciones en que lo recibió especialmente en lo referente a la pintura general del mismo. El inmueble deberá restituirse a paz y salvo por concepto de cánones, cuotas de administración y servicios En caso de que existan obligaciones pendientes de pago a cargo de EL ARRENDATARIO o que el inmueble no esté en las condiciones pactadas para su restitución, el ARRENDADOR podrá negarse a recibir el inmueble. En este caso el ARRENDATARIO mantendrá a su cargo las obligaciones contraídas en virtud de este contrato, sin que por ello se entienda prorrogado el mismo.";
		
		$texto .= "<br>"."<b>DECIMA CUARTA - EXENCION DE RESPONSABILIDAD.</b>   EL ARRENDADOR no asume responsabilidad alguna por los daños o perjuicios que ELARRENDATARIO pueda sufrir por caso fortuito, fuerza mayor o causas atribuibles a terceros EL ARRENDATARIO asume la responsabilidad por los daños que se puedan causar al inmueble o a los enseres y dotaciones de los vecinos o terceros, cuando estos provengan del descuido o negligencia de EL ARRENDATARIO, de sus dependientes, familia, huéspedes o subarrendatarios. De la misma manera no serán responsables por los deterioros que sufran los muebles, enseres, electrodomésticos, etc. depositados en la vivienda, por causa no imputables directamente a su voluntad. No serán responsables por defectos de construcción no conocidas. Ni el ARRENDADOR ni el propietario del inmueble en ningún caso será responsables o solidarios con indemnizaciones o hechos generados por ilícitos cometidos u ocurridos en el inmueble, como tampoco por daños, siniestros, inundaciones, incendio etc. Que puedan causar daño a los muebles, enseres, electrodomésticos u objetos que se guarden en el inmueble, y el cuidado o seguros.";
		
		$texto .= "<br>"."<b>DÉCIMO QUINTA - CLAUSULA PENAL.</b>   El incumplimiento por parte de cualquiera de las partes de todas o alguna de las cláusulas de este contrato, y aun el simple retardo en el pago de una o más mensualidades por parte del ARRENDATARIO, constituirá a la parte que haya incumplida en DEUDOR de la otra, por una suma equivalente a (3) tres cánones de arrendamiento, que esté vigente en el momento en que tal incumplimiento se presente a título de pena, más el IVA que se genere por este pago. Se entenderá, en todo extingue la obligación principal y que la parte afectada podrá pedir a la vez, si es el caso. Este contrato será prueba sumaria suficiente para el cobro de esta pena y el arrendador, arrendatario y/o deudores solidarios renuncian expresamente a cualquier requerimiento privado o judicial para constituirlos en mora del pago de esta o cualquier otra obligación derivada del contrato. <b>PARAGRAFO:</b> ARRENDADOR, ARRENDATARIO y DEUDORES SOLIDARIOS podrán denominarse conjuntamente LAS PARTES. De Esta forma, LAS PARTES declaran conocer que el presente contrato es suscrito durante la vigencia de las normas relativas a la emergencia sanitaria, el estado de emergencia económica y la limitación a la libre circulación de personas y vehículos en todo el territorio nacional, normas que entre otras, han sido emitidas por el Gobierno Nacional como parte de las medidas tendientes a controlar la propagación del virus COVID-19, atendiendo a la declaración de pandemia respecto de dicho virus emitida por la Organización Mundial de la Salud OMS que ha instado a todos los Estados del mundo a tomar acciones que lleven a controlar la propagación del COVID-19; teniendo en cuenta lo anterior LAS PARTES no podrán argumentar la Fuerza Mayor como causal para el incumplimiento de las obligaciones que se adquieren, y así lo declaran y aceptan.";
		
		$texto .= "<br>"."<b>DECIMO SEXTA - AUTORIZACION PARA EL REPORTE DE INFORMACIÓN POSITIVA Y NEGATIVA</b>     Los firmantes de este contrato, en nuestra calidad, como titulares ce la información, actuando libres y voluntariamente, autorizamos de manera expresa e irrevocable a ARRENDADOR, a  LOGICAL GROWTH SAS a quien represente sus derechos; a consultar, solicitar, suministrar reportar, procesar y divulgar toda la información que se reitera a terceros países de la misma naturaleza a la Central de información -CIFIN- que administra Bancaria y de Entidades Financieras de Colombia, o a quien represente sus derechos. Declaramos que somos conscientes que el alcance de esta autorización implica que el comportamiento frente a nuestras obligaciones será registrado con el objeto de suministrar información suficiente y adecuada al mercado sobre el estado de nuestras obligaciones financieras, comerciales, crediticias, de servicios y la proveniente de terceros países de la misma naturaleza. En consecuencia, quienes se encuentren afiliados y/o tengan acceso a la Central de Información -CIFIN- podrán conocer esta información, de conformidad con la legislación jurisprudencia aplicable a la materia. La información podrá ser igualmente utilizada para efectos estadísticos. Nuestros derechos corresponden a lo determinado por el ordenamiento jurídico aplicable del cual, por ser de carácter público, estamos enterados. Así mismo, manifestamos que conocemos el contenido el reglamento de la CIFIN. En caso de que, en el futuro, el autorizado en este documento efectué, a favor de un tercero, una venta de cartera, una representación, una gestión, una cesión a cualquier título, entre otros aspectos, de obligaciones a nuestro caro, los efectos de la presente autorización se extenderán a éste en los mismo términos y condiciones. Así mismo, autorizamos a la Central de información a que, en su calidad de operador, ponga nuestra información a disposición de otros operadores nacionales o extranjeros, en los términos que establece la ley, siempre y cuando su objeto sea similar al aquí establecido.";
		
		$texto .= "<br>"."<b>DECIMO SEPTIMA - ABANDONO DEL INMUEBLE.</b>   Al suscribir este contrato EL ARRENDATARIO faculta expresamente a EL ARRENDADOR para perpetrar el inmueble y recuperar su tenencia con el solo requisito de la presencia de dos testigos, en procura de evitar el deterioro o el desmantelamiento de tal inmueble siempre que por cualquier circunstancia el mismo permanezca abandonado y/o desocupado por el termino de treinta (30) días y que la exposición al riesgo sea tal que amenace la integridad física del bien o la seguridad del vecindario.";
		
		$texto .= "<br>"."<b>DECIMO OCTAVA - COBRO EXTRAJUDICIAL.</b>  Si el incumplimiento de la obligación de cancelar oportunamente los cánones de arrendamiento, servicios públicos, cuotas de administración, o cualquier otra erogación derivada del contrato, diere lugar a alguna diligencia de cobro extrajudicial, EL ARRENDATARIO se obliga a pagar a la entidad encargada de tal gestión, los costos correspondientes.";

		$texto .= "<br>"."<b>DECIMO NOVENA - CUARTA MERITO EJECUTIVO.</b>   El presente contrato, junto con los documentos a que haya lugar de conformidad con la ley, presta mérito ejecutivo para exigir el pago de la suma estipulada como cláusula penal, los cánones de arrendamiento que se adecuen, os servicios públicos, cuotas de administración o intereses, así como cualquier otra suma a cargo de EL ARRENDATARIO.";
		
		$texto .= "<br>"."<b>VIGESIMA - CAUSALES DE TERMINACIÓN DEL CONTRATO DE ARRENDAMIENTO POR PARTE DEL ARRENDADOR.</b> EL ARRENDADOR además de las causales que señala la ley se podrá dar por terminado el presente contrato de arrendamiento por las siguientes Causales 1). El no pago del canon de arrendamiento y cuotas de administración si las hubiere, de los servicios términos pactados. 2) Cuando en uno de los servicios públicos cause la suspensión, desconexión o pérdida del servicio. 3). Cuando se subarriende total o parcialmente el inmueble, se ceda o se dé una destinación distinta a la pactada en el contrato. 4). Cuando EL ARRENDATARIO reiteradamente afecte la tranquilidad los vecinos o destine el inmueble para actos delictivos o que impliquen contravención. 5) Cuando EL ARRENDATARIO realice mejoras, adiciones, cambios o ampliaciones en el inmueble, o lo destruya total o parcialmente. 6). Cuando EL ARRENDATARIO viole las normas del respectivo reglamento interno o propiedad horizontal, o no pague las expensas comunes cuando el pago este a su cargo 7). Cuan PROPIETARIO o POSEEDOR necesite el inmueble para ocuparlo, o cuando el inmueble haya de demolerse para efectuar una nueva construcción, o cuando se requiere desocupado con el fin de ejecutar obras indispensables para su reparación, entre otras establecidas en la ley aplicable a la materia 8). Cuando e inmueble haya de entregarse en cumplimiento de las obligaciones originadas en un contrato de compraventa.";
		
		$texto .= "<br>"."<b>VIGESIMA PRIMERA - CAUSALES DE TERMINACIÓN DEL CONTRATO DE ARRENDAMIENTO POR PARTE DE EL ARRENDATARIO.</b> Sin perjuicio de las causales legales que resulten aplicables, las partes acuerdan especialmente que son justas causas para que EL ARRENDATARIO pueda dar por terminado unilateralmente el presente contrato, las siguientes: 1) la suspensión de la prestación de los servicios públicos al inmueble, por acción premeditada de ELARRENDADOR, 2) la incursión reiterada de EL ARRENDADCR en procederes que afecten gravemente el disfrute cabal por EL ARRENDATARIO del inmueble arrendado, debidamente comprobada ante la autoridad policiva, 3) el desconocimiento por parte de EL ARRENDADOR de derechos reconocidos a EL ARRENDATARIO en este contrato o por la Ley, y 4) EL ARRENDATARIO podrá dar por terminado unilateralmente el contrato de arrendamiento, para lo cual aplicará el procedimiento establecido por ley para el efecto, incluyendo la indemnización allí estipulada para este caso. 5) EL ARRENDATARIO podrá dar por terminado unilateralmente el contrato de arrendamiento a la fecha de vencimiento del término inicial o de sus prorrogas, siempre y cuando dé previo aviso escrito al ARRENDADOR a través del servicio postal autorizado, con una antelación no menor de Noventa (90) Días a la referida fecha de vencimiento. La terminación unilateral por parte de arrenda ario en cualquier momento solo se aceptará previo al pago de una indemnización equivalente al precio de TRES (03) cánones de arrendamiento vigentes al momento de entrega del inmueble.";
		
		$texto .= "<br>"."<b>VIGESIMA SEGUNDA - INSPECCION.</b>  El arrendatario permitirá las visitas que en cualquier tiempo el arrendador o sus representantes tengan a bien realizar, para constatar el estado y conservación del inmueble u otras circunstancias que sean de su interés.";
		
		$texto .= "<br>"."<b>VIGESIMA TERCERA - ASPECTO TRIBUTARIO.</b>   Las partes contratantes dejan expresa constancia de que el beneficiario directo de los pagos por concepto de los cánones de arrendamiento es el propietario del inmueble arrendado y en consecuencia la retención en la fuente será practicada al mismo y no al ARRENDADOR quien obra como intermediario.";
		
		$texto .= "<br>"."<b>VIGESIMA CUARTA - VARIOS.</b>  EL ARRENDATARIO pagará por su cuenta todos los gastos que ocasione el contrato, los de su prórroga, o de renovación llegado el caso. Igualmente, EL ARRENDATARIO manifiesta que ha recibido copia del presente contrato a entera satisfacción, con las firmas originales, así como de la normativa del Reglamento de Propiedad Horizontal (este último en caso de que haya lugar a este). El ARRENDATARIO faculta expresamente al ARRENDADOR para llenar en este contrato los espacios en blanco.";
		
		$texto .= "<br>"."<b>VIGESIMA QUINTA - NOTIFICACIONES.</b>  Para los efectos del artículo 12 de la Ley 820 del 2003, Las notificaciones serán recibidas en las direcciones que aparecen frente a la firma de los abajo firmantes.";
		
		$texto .= "<br>"."<b>VIGESIMA SEXTA – DOMICILIO CONTRACTUAL</b>   Para Efectos contractuales se tendrá como domicilio la ciudad de Pereira. El arrendatario ". $inmueble_direccion .". EL ARRENDADOR, CALLE 8 BIS N° 15-33 LOCAL 204 EDIFICIO PORTAL DE LOS ALPES de la ciudad de Pereira.";
		
		$texto .= "<br>"."<b>VIGESIMA SEPTIMA-HEREDEROS:</b> Si muere EL ARRENDATARIO, EL ARRENDADOR puede acogerse al ARTICULO 1434 del Código Civil, respecto de elegir uno cualquiera de los herederos a su elección y seguir con el juicio sin demandar ni notificar a los demás.";
		
		$texto .= "<br>"."En señal de aceptación del contenido del presente contrato, las partes Libre y voluntariamente lo firman en la ciudad de ". $contrato_ciudad .", a los ". $contrato_fecha_firma_dia ." días del mes de ". $contrato_fecha_firma_mes ." del año ". $contrato_fecha_firma_year.".";
			  
		$texto .= "<br><br><br><br>"."
		_____________________________________<br>
		<b>". $arrendatario_nombre ."</b><br>
		<b>CC ". $arrendatario_cc ." de ". $arrendatario_cc_expedicion ."</b><br>
		<b>ARRENDATARIO.</b><br>
		Correo: ". $arrendatario_correo ."<br>
		Cel: ". $arrendatario_celular ."";
			 
		if ($solidario1){
			$texto .= "<br><br><br><br>"."
			_____________________________________<br>
			<b>". $solidario1_nombre ."</b><br>
			<b>CC N° ". $solidario1_cc ." de ". $solidario1_cc_expedicion ."</b><br>
			<b>PRIMER CODEUDOR.</b><br>
			Correo: ". $solidario1_correo ."<br>
			Cel: ". $solidario1_celular ."";
		}
		
		if ($solidario2){
			$texto .= "<br><br><br><br>"."
			_____________________________________<br>
			<b>". $solidario2_nombre ."</b><br>
			<b>CC N° ". $solidario2_cc ." de ". $solidario2_cc_expedicion ."</b><br>
			<b>SEGUNDO CODEUDOR.</b><br>
			Correo: ". $solidario2_correo ."<br>
			Cel: ". $solidario2_celular ."";
		}

		if ($solidario3){
			$texto .= "<br><br><br><br>"."
			_____________________________________<br>
			<b>". $solidario3_nombre ."</b><br>
			<b>CC N° ". $solidario3_cc ." de ". $solidario3_cc_expedicion ."</b><br>
			<b>TERCER CODEUDOR.</b><br>
			Correo: ". $solidario3_correo ."<br>
			Cel: ". $solidario3_celular ."";
		}
		
		$texto .= "<br><br><br><br>"."
		_____________________________________<br>
		<b>ALEJANDRA CATALINA BARROS IBAÑEZ</b><br>
		<b>CC 42.132.379 de Pereira</b><br>
		<b>Representante Legal</b><br>
		<b>GRUPO SUKASA S.A.S.</b><br>
		<b>Nit: 900.753.888-9.</b><br>
		<b>ARRENDADOR.</b><br>
		<b>servicioalcliente@gruposukasa.co</b>
		";

		global $conf;

		// Force to disable hidetop and hidebottom
		$hidebottom = 0;
		if ($hidetop) {
			$hidetop = -1;
		}
		$pdf->WriteHTML($texto);
		//$pdf->MultiCell(0, 8, $texto,0,'J',0);
	}


	/**
	 * Show footer signature of page
	 * @param   TCPDF       $pdf            Object PDF
	 * @param   int         $tab_top        tab height position
	 * @param   int         $tab_height     tab height
	 * @param   Translate   $outputlangs    Object language for output
	 * @return void
	 */
	protected function tabSignature(&$pdf, $tab_top, $tab_height, $outputlangs)
	{
		$pdf->SetDrawColor(128, 128, 128);
		$posmiddle = $this->marge_gauche + round(($this->page_largeur - $this->marge_gauche - $this->marge_droite) / 2);
		$posy = $tab_top + $tab_height + 3 + 3;

		$pdf->SetXY($this->marge_gauche, $posy);
		$pdf->MultiCell($posmiddle - $this->marge_gauche - 5, 5, $outputlangs->transnoentities("ContactNameAndSignature", $this->emetteur->name), 0, 'L', 0);

		$pdf->SetXY($this->marge_gauche, $posy + 5);
		$pdf->MultiCell($posmiddle - $this->marge_gauche - 5, 20, '', 1);

		$pdf->SetXY($posmiddle + 5, $posy);
		$pdf->MultiCell($this->page_largeur - $this->marge_droite - $posmiddle - 5, 5, $outputlangs->transnoentities("ContactNameAndSignature", $this->recipient->name), 0, 'L', 0);

		$pdf->SetXY($posmiddle + 5, $posy + 5);
		$pdf->MultiCell($this->page_largeur - $this->marge_droite - $posmiddle - 5, 20, '', 1);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 *  Show top header of page.
	 *
	 *  @param	TCPDF		$pdf     		Object PDF
	 *  @param  Contrat		$object     	Object to show
	 *  @param  int	    	$showaddress    0=no, 1=yes
	 *  @param  Translate	$outputlangs	Object lang for output
	 *  @return	void
	 */
	protected function _pagehead(&$pdf, $object, $showaddress, $outputlangs)
	{
		global $conf, $langs;

		$default_font_size = pdf_getPDFFontSize($outputlangs);

		// Load traductions files required by page
		$outputlangs->loadLangs(array("main", "dict", "contract", "companies"));

		pdf_pagehead($pdf, $outputlangs, $this->page_hauteur);

		//Affiche le filigrane brouillon - Print Draft Watermark
		if ($object->statut == 0 && (!empty($conf->global->CONTRACT_DRAFT_WATERMARK))) {
			pdf_watermark($pdf, $outputlangs, $this->page_hauteur, $this->page_largeur, 'mm', $conf->global->CONTRACT_DRAFT_WATERMARK);
		}

		//Prepare next
		$pdf->SetTextColor(0, 0, 60);
		$pdf->SetFont('', 'B', $default_font_size + 3);

		$posx = $this->page_largeur - $this->marge_droite - 100;
		$posy = $this->marge_haute;

		$pdf->SetXY($this->marge_gauche, $posy);

		
		// Logo
		//$logo = $conf->mycompany->dir_output.'/logos/'.$this->emetteur->logo;
		$bar = "https://imgur.com/a7JhB95.png";
		$logo = "https://imgur.com/S2ZbDTQ.png";
		$pdf->Image($bar, 0, 0, 0, $height);
		$pdf->Image($logo, 110, $posy, 0, 35);
		if ($this->emetteur->logo) {
			if (is_readable($logo)) {
				$height = pdf_getHeightForLogo($logo);
				//$pdf->Image($logo, $this->marge_gauche, $posy, 0, $height); // width=0 (auto)
			} else {
				//$pdf->SetTextColor(200, 0, 0);
				//$pdf->SetFont('', 'B', $default_font_size - 2);
				//$pdf->MultiCell(100, 3, $outputlangs->transnoentities("ErrorLogoFileNotFound", $logo), 0, 'L');
				//$pdf->MultiCell(100, 3, $outputlangs->transnoentities("ErrorGoToGlobalSetup"), 0, 'L');
			}
		} else {
			$text = $this->emetteur->name;
			//$pdf->MultiCell(100, 4, $outputlangs->convToOutputCharset($text), 0, 'L');
		}

		/* $pdf->SetFont('', 'B', $default_font_size + 3);
		$pdf->SetXY($posx, $posy);
		$pdf->SetTextColor(0, 0, 60);
		$title = $outputlangs->transnoentities("ContractCard");
		$pdf->MultiCell(100, 4, $title, '', 'R');

		$pdf->SetFont('', 'B', $default_font_size + 2);

		$posy += 5;
		$pdf->SetXY($posx, $posy);
		$pdf->SetTextColor(0, 0, 60);
		$pdf->MultiCell(100, 4, $outputlangs->transnoentities("Ref")." : ".$outputlangs->convToOutputCharset($object->ref), '', 'R');

		$posy += 1;
		$pdf->SetFont('', '', $default_font_size);

		$posy += 4;
		$pdf->SetXY($posx, $posy);
		$pdf->SetTextColor(0, 0, 60);
		$pdf->MultiCell(100, 3, $outputlangs->transnoentities("Date")." : ".dol_print_date($object->date_contrat, "day", false, $outputlangs, true), '', 'R'); */

		/* if ($object->thirdparty->code_client) {
			$posy += 4;
			$pdf->SetXY($posx, $posy);
			$pdf->SetTextColor(0, 0, 60);
			$pdf->MultiCell(100, 3, $outputlangs->transnoentities("CustomerCode")." : ".$outputlangs->transnoentities($object->thirdparty->code_client), '', 'R');
		} */

		/* if ($showaddress) {
			// Sender properties
			$carac_emetteur = '';
			// Add internal contact of proposal if defined
			$arrayidcontact = $object->getIdContact('internal', 'INTERREPFOLL');
			if (count($arrayidcontact) > 0) {
				$object->fetch_user($arrayidcontact[0]);
				$carac_emetteur .= ($carac_emetteur ? "\n" : '').$outputlangs->transnoentities("Name").": ".$outputlangs->convToOutputCharset($object->user->getFullName($outputlangs))."\n";
			}

			$carac_emetteur .= pdf_build_address($outputlangs, $this->emetteur, $object->thirdparty, '', 0, 'source', $object);

			// Show sender
			$posy = 42;
			$posx = $this->marge_gauche;
			if (!empty($conf->global->MAIN_INVERT_SENDER_RECIPIENT)) {
				$posx = $this->page_largeur - $this->marge_droite - 80;
			}
			$hautcadre = 40;

			// Show sender frame
			$pdf->SetTextColor(0, 0, 0);
			$pdf->SetFont('', '', $default_font_size - 2);
			$pdf->SetXY($posx, $posy - 5);
			$pdf->SetXY($posx, $posy);
			$pdf->SetFillColor(230, 230, 230);
			$pdf->MultiCell(82, $hautcadre, "", 0, 'R', 1);

			// Show sender name
			$pdf->SetXY($posx + 2, $posy + 3);
			$pdf->SetTextColor(0, 0, 60);
			$pdf->SetFont('', 'B', $default_font_size);
			$pdf->MultiCell(80, 3, $outputlangs->convToOutputCharset($this->emetteur->name), 0, 'L');
			$posy = $pdf->getY();

			// Show sender information
			$pdf->SetFont('', '', $default_font_size - 1);
			$pdf->SetXY($posx + 2, $posy);
			$pdf->MultiCell(80, 4, $carac_emetteur, 0, 'L');


			// If CUSTOMER contact defined, we use it
			$usecontact = false;
			$arrayidcontact = $object->getIdContact('external', 'CUSTOMER');
			if (count($arrayidcontact) > 0) {
				$usecontact = true;
				$result = $object->fetch_contact($arrayidcontact[0]);
			}

			$this->recipient = $object->thirdparty;

			// Recipient name
			if ($usecontact && ($object->contact->socid != $object->thirdparty->id && (!isset($conf->global->MAIN_USE_COMPANY_NAME_OF_CONTACT) || !empty($conf->global->MAIN_USE_COMPANY_NAME_OF_CONTACT)))) {
				$thirdparty = $object->contact;
			} else {
				$thirdparty = $object->thirdparty;
			}

			$this->recipient->name = pdfBuildThirdpartyName($thirdparty, $outputlangs);

			$carac_client = pdf_build_address($outputlangs, $this->emetteur, $object->thirdparty, (isset($object->contact) ? $object->contact : ''), $usecontact, 'target', $object);

			// Show recipient
			$widthrecbox = 100;
			if ($this->page_largeur < 210) {
				$widthrecbox = 84; // To work with US executive format
			}
			$posy = 42;
			$posx = $this->page_largeur - $this->marge_droite - $widthrecbox;
			if (!empty($conf->global->MAIN_INVERT_SENDER_RECIPIENT)) {
				$posx = $this->marge_gauche;
			}

			// Show recipient frame
			$pdf->SetTextColor(0, 0, 0);
			$pdf->SetFont('', '', $default_font_size - 2);
			$pdf->SetXY($posx + 2, $posy - 5);
			$pdf->Rect($posx, $posy, $widthrecbox, $hautcadre);
			$pdf->SetTextColor(0, 0, 0);

			// Show recipient name
			$pdf->SetXY($posx + 2, $posy + 3);
			$pdf->SetFont('', 'B', $default_font_size);
			$pdf->MultiCell($widthrecbox, 4, $this->recipient->name, 0, 'L');

			$posy = $pdf->getY();

			// Show recipient information
			$pdf->SetFont('', '', $default_font_size - 1);
			$pdf->SetXY($posx + 2, $posy);
			$pdf->MultiCell($widthrecbox, 4, $carac_client, 0, 'L');
		} */
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 *   	Show footer of page. Need this->emetteur object
	 *
	 *   	@param	PDF			$pdf     			PDF
	 * 		@param	Contrat		$object				Object to show
	 *      @param	Translate	$outputlangs		Object lang for output
	 *      @param	int			$hidefreetext		1=Hide free text
	 *      @return	integer
	 */
	protected function _pagefoot(&$pdf, $object, $outputlangs, $hidefreetext = 0)
	{
		global $conf;
		$showdetails = empty($conf->global->MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS) ? 0 : $conf->global->MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS;
		return pdf_pagefoot($pdf, $outputlangs, 'CONTRACT_FREE_TEXT', $this->emetteur, $this->marge_basse, $this->marge_gauche, $this->page_hauteur, $object, $showdetails, $hidefreetext);
	}
}
