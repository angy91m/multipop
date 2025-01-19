<?php
defined('ABSPATH') || exit;
ob_start();
?>
    <style type="text/css">
        h2 {color: #222; line-height: 8px;}
        span.sugg {font-size: 9pt;}
    </style>
    <p></p>
    <span style="font-family: 'helveticatitle'">
        <h2>Richiesta di adesione all’associazione Multipopolare APS</h2>
        <h2 style="font-family: 'helveticalight'; font-size: 12pt; line-height: 12px;">Autorizzazione al trattamento dei dati personali ai sensi dell’art. 13<br/>del Regolamento (UE) 2016/679</h2>
    </span>
    <p style="font-family: 'helveticalight'; font-size: 12pt; line-height: 15px;">Il/La sottoscritto/a <span class="sugg">(nome cognome)</span>&nbsp;<u><?=$this->nbsp(80)?></u><br/>
        nato/a a/in&nbsp;<u><?=$this->nbsp(101)?></u>&nbsp;(<u><?=$this->nbsp(8)?></u>)<br/>
        il <span class="sugg">(gg/mm/aaaa)</span>&nbsp;<u><?=$this->nbsp(8)?></u>/<u><?=$this->nbsp(8)?></u>/<u><?=$this->nbsp(16)?></u><br/>
        residente a/in&nbsp;<u><?=$this->nbsp(97)?></u>&nbsp;(<u><?=$this->nbsp(8)?></u>)<br/>
        indirizzo&nbsp;<u><?=$this->nbsp(116)?></u><br/>
        cap&nbsp;<u><?=$this->nbsp(30)?></u><br/>
        telefono&nbsp;<u><?=$this->nbsp(64)?></u><br/>
        email&nbsp;<u><?=$this->nbsp(121)?></u><br/><br/>
        <span style="font-family: 'helveticamedium'">chiede di aderire all’associazione Multipopolare APS</span><br/>
        <span style="color: #aaa; font-size:10pt;">(A cura di Multipopolare)</span><br/>
        Quota tessera:&nbsp;&nbsp;<u><?=$this->nbsp(18)?></u><?=$this->nbsp(4)?>Numero tessera:&nbsp;&nbsp;<u><?=$this->nbsp(36)?></u><?=$this->nbsp(4)?>Anno tessera:&nbsp;&nbsp;<u><?=$this->nbsp(10)?></u>
    </p>
    <p style="font-size: 9pt; line-height: 9px; font-family: 'helveticalight'; text-align: justify">Acquisite le informazioni fornite dal titolare del trattamento ai sensi dell’articolo 7 del Regolamento (UE) 2016/679 con la presente acconsente al trattamento dei propri dati personali da parte di MULTIPOPOLARE APS per le finalità esposte nell’informativa consegnatami <span style="font-family: 'helveticamedium'">ad esclusione delle attività di marketing</span>, iscrizione a newsletter automatizzata dell’associazione, pubblicazione su supporto cartaceo ed elettronico dei dati personali, di trasferimento dei dati personali in un paese extra UE.</p>
    <p style="font-size: 9pt; line-height: 9px; font-family: 'helveticalight';">Luogo e data</p>
<?php
$pdf->WriteHTML(ob_get_clean(), true, false, true);
$pdf->SetY($pdf->GetY()-4);
// START RENEW CHECK
$old_line = [$pdf->GetX(), $pdf->GetY()];
ob_start();
$pdf->SetY(18.3);
?>
    <style type="text/css">
        h2 {color: #222; line-height: 8px;}
        span.sugg {font-size: 9pt;}
    </style>
    <span style="font-family: 'helveticatitle'">
        <h2>MODULO CONSENSO SOCIO</h2>
    </span>
<?php
$pdf->WriteHTML(ob_get_clean(), true, false, true);
$pdf->SetY(32);
$next_line = [$pdf->GetX()+2, $pdf->GetY()+5];
$pdf->Rect(150, 30, 20, 6);
//$pdf->RegularPolygon($next_line[0]+15, $next_line[1], 2, 4, 45, false, '', [], [50, 34, 34]);
$pdf->SetY($next_line[1]-1.7);
ob_start();
?>
    <p style="font-size: 7pt; line-height: 9px; font-family: 'helveticalight';"><?=$this->nbsp(70)?>In caso di rinnovo, oltre ad indicare nome, cognome ed eventuali dati cambiati,<br/><?=$this->nbsp(70)?>inserire un dato non cambiato (indirizzo e-mail o telefono) nel seguente spazio:</p>
<?php
$pdf->WriteHTML(ob_get_clean(), true, false, true);
$pdf->SetX($old_line[0]);
$pdf->SetY($old_line[1]);

// END RENEW CHECK
ob_start();
?>
    <p style="font-size: 9pt; line-height: 9px; font-family: 'helveticalight'; text-align: right;">Firma<?=$this->nbsp(32)?></p>
    <p style="font-size: 9pt; line-height: 9px; font-family: 'helveticalight';"><u><?=$this->nbsp(60)?></u>&nbsp;(<u><?=$this->nbsp(9)?></u>), <u><?=$this->nbsp(9)?></u>/<u><?=$this->nbsp(9)?></u>/<u><?=$this->nbsp(18)?></u></p>
    <p style="line-height:15px"></p>
    <p style="font-size: 9pt; line-height: 9px; font-family: 'helveticalight'; text-align: justify"><span style="font-family: 'helveticamedium'">Consenso a trattamenti ulteriori, consigliati ma non indispensabili per la continuazione del rapporto (selezionare le voci)</span><br/><br/>
        Presta il suo consenso e fino alla revoca dello stesso, per la proposizione di offerte, comunicazioni commerciali e per il successivo invio di materiale informativo pubblicitario e/o promozionale e/o sondaggi di opinione, ricerche di mercato, invio di newsletter di MULTIPOPOLARE APS (di seguito complessivamente definite “attività di propaganda”) del Titolare e/o da organizzazioni correlate. Il trattamento per attività di marketing avverrà con modalità “tradizionali” (a titolo esemplificativo posta cartacea e/o chiamate da operatore), ovvero mediante sistemi “automatizzati” di contatto (a titolo esemplificativo SMS e/o MMS, chiamate telefoniche senza l’intervento dell’operatore, posta elettronica, social network, newsletter, applicazioni interattive, notifiche push)
    </p>
<?php
$pdf->WriteHTML(ob_get_clean(), true, false, true);
$next_line = [$pdf->GetX()+2, $pdf->GetY()+5];
$pdf->RegularPolygon($next_line[0],$next_line[1], 2, 4, 45, false, '', [], [34, 34, 34]);
$pdf->RegularPolygon($next_line[0]+40,$next_line[1], 2, 4, 45, false, '', [], [34, 34, 34]);
$pdf->setY($next_line[1]-1.7);
ob_start();
?>
    <p style="font-size: 9pt; line-height: 9px; font-family: 'helveticalight';"><?=$this->nbsp(6)?>Presto il consenso<?=$this->nbsp(17)?>Nego il consenso</p>
<?php
$pdf->WriteHTML(ob_get_clean(), true, false, true);
$pdf->SetY($pdf->GetY()-4);
ob_start();
?>
    <p style="font-size: 9pt; line-height: 9px; font-family: 'helveticalight'; text-align: right">Firma<?=$this->nbsp(32)?></p>
    <p style="font-size: 9pt; line-height: 9px; font-family: 'helveticalight';">Luogo e data</p>
    <p style="font-size: 9pt; line-height: 9px; font-family: 'helveticalight';"><u><?=$this->nbsp(60)?></u>&nbsp;(<u><?=$this->nbsp(9)?></u>), <u><?=$this->nbsp(9)?></u>/<u><?=$this->nbsp(9)?></u>/<u><?=$this->nbsp(18)?></u></p>
    <p style="font-size: 9pt; line-height: 9px; font-family: 'helveticalight'; text-align: justify">Presta il suo consenso e fino alla revoca dello stesso, per la comunicazioni di iniziative ed attività di MULTIPOPOLARE APS (di seguito complessivamente definite “attività di informazione dell’associazione”) del Titolare e/o da organizzazioni correlate.<br/>Il trattamento per attività di informazione dell’associazione avverrà con modalità “tradizionali” (a titolo esemplificativo posta cartacea), ovvero mediante sistemi “automatizzati” di contatto (a titolo esemplificativo posta elettronica)</p>
<?php
$pdf->WriteHTML(ob_get_clean(), true, false, true);
$next_line = [$pdf->GetX()+2, $pdf->GetY()+5];
$pdf->RegularPolygon($next_line[0],$next_line[1], 2, 4, 45, false, '', [], [34, 34, 34]);
$pdf->RegularPolygon($next_line[0]+40,$next_line[1], 2, 4, 45, false, '', [], [34, 34, 34]);
$pdf->setY($next_line[1]-1.7);
ob_start();
?>
    <p style="font-size: 9pt; line-height: 9px; font-family: 'helveticalight';"><?=$this->nbsp(6)?>Presto il consenso<?=$this->nbsp(17)?>Nego il consenso</p>
<?php
$pdf->WriteHTML(ob_get_clean(), true, false, true);
$pdf->SetY($pdf->GetY()-4);
ob_start();
?>
    <p style="font-size: 9pt; line-height: 9px; font-family: 'helveticalight'; text-align: right">Firma<?=$this->nbsp(32)?></p>
    <p style="font-size: 9pt; line-height: 9px; font-family: 'helveticalight';">Luogo e data</p>
    <p style="font-size: 9pt; line-height: 9px; font-family: 'helveticalight';"><u><?=$this->nbsp(60)?></u>&nbsp;(<u><?=$this->nbsp(9)?></u>), <u><?=$this->nbsp(9)?></u>/<u><?=$this->nbsp(9)?></u>/<u><?=$this->nbsp(18)?></u></p>
    <p style="font-size: 9pt; line-height: 9px; font-family: 'helveticalight'; text-align: justify">Presta il suo consenso e fino alla revoca dello stesso, per la pubblicazione del suo nominativo su riviste, cataloghi, brochure, annuari, siti, ecc. di MULTIPOPOLARE APS (di seguito complessivamente definite “attività di pubblicazione dell’associazione”) del Titolare e/o da organizzazioni correlate. Il trattamento per attività di pubblicazione dell’associazione avverrà con modalità “tradizionali” (a titolo esemplificativo pubblicazioni cartacee), ovvero mediante sistemi “elettronici” (a titolo esemplificativo pubblicazioni elettroniche, social network, sito, blog, ecc.)</p>
<?php
$pdf->WriteHTML(ob_get_clean(), true, false, true);
$next_line = [$pdf->GetX()+2, $pdf->GetY()+5];
$pdf->RegularPolygon($next_line[0],$next_line[1], 2, 4, 45, false, '', [], [34, 34, 34]);
$pdf->RegularPolygon($next_line[0]+40,$next_line[1], 2, 4, 45, false, '', [], [34, 34, 34]);
$pdf->setY($next_line[1]-1.7);
ob_start();
?>
    <p style="font-size: 9pt; line-height: 9px; font-family: 'helveticalight';"><?=$this->nbsp(6)?>Presto il consenso<?=$this->nbsp(17)?>Nego il consenso</p>
<?php
$pdf->WriteHTML(ob_get_clean(), true, false, true);
$pdf->SetY($pdf->GetY()-4);
ob_start();
?>
    <p style="font-size: 9pt; line-height: 9px; font-family: 'helveticalight'; text-align: right">Firma<?=$this->nbsp(32)?></p>
    <p style="font-size: 9pt; line-height: 9px; font-family: 'helveticalight';">Luogo e data</p>
    <p style="font-size: 9pt; line-height: 9px; font-family: 'helveticalight';"><u><?=$this->nbsp(60)?></u>&nbsp;(<u><?=$this->nbsp(9)?></u>), <u><?=$this->nbsp(9)?></u>/<u><?=$this->nbsp(9)?></u>/<u><?=$this->nbsp(18)?></u></p>
<?php
$pdf->WriteHTML(ob_get_clean(), true, false, true);
$pdf->AddPage();
ob_start();
?>
    <p></p>
    <h2 style="font-family: 'helveticalmedium'; font-size: 9pt; line-height: 0px;">Informativa sul trattamento dei dati personali ai sensi dell’art. 13 del Regolamento (UE) 2016/679</h2>
    <p style="font-size: 8pt; line-height: 8px; font-family: 'helveticalight'; text-align: justify">Al socio, di seguito interessato
        <br/><span style="font-family:'helveticamedium'">MULTIPOPOLARE APS</span> nella qualità di Titolare del trattamento dei Suoi dati personali, ai sensi e per gli effetti del Regolamento (UE) 2016/679 cioè GDPR, con la presente La informa che la citata normativa prevede la tutela degli interessati rispetto al trattamento dei dati personali e che tale trattamento sarà improntato ai principi di correttezza, liceità, trasparenza e di tutela della Sua riservatezza e dei Suoi diritti.
        <br/>I Suoi dati personali verranno trattati in accordo alle disposizioni legislative della normativa sopra richiamata e degli obblighi di riservatezza ivi previsti.
        <br/><span style="font-family:'helveticamedium'">Finalità di trattamento</span>
        <br/>In particolare i Suoi dati verranno trattati per le seguenti finalità connesse all’attuazione di adempimenti relativi ad obblighi legislativi o contrattuali: Adempimenti obbligatori per legge in campo fiscale e contabile; Assistenza post iscrizione o donazione; Attività organizzative funzionali all’adesione all’associazione (soci); Gestione del contenzioso; Gestione degli associati e dei donatori; Programmazione delle attività; Storico associati/donatori; Aggiornamento informativo periodico; Attività di pubblicazione. Il trattamento dei dati funzionali per l’espletamento di tali obblighi è necessario per una corretta gestione del rapporto tra titolare e sostenitore e il loro conferimento è obbligatorio per attuare le finalità sopra indicate. Il Titolare rende noto, inoltre, che l’eventuale non comunicazione, o comunicazione errata, di una delle informazioni obbligatorie, può causare l’impossibilità del Titolare di garantire la congruità del trattamento stesso.
        <br/><span style="font-family:'helveticamedium'">Modalità del trattamento</span>
        <br/>I suoi dati personali potranno essere trattati nei seguenti modi: Trattamento a mezzo di sistemi di elaborazione elettronica; Trattamento manuale a mezzo di archivi cartacei; Comunicazione.
        <br/>I suoi dati saranno trattati unicamente da personale adeguatamente formato ed autorizzato.
        <br/><span style="font-family:'helveticamedium'">Diffusione e trasferimento</span>
        <br/>I suoi dati personali non verranno diffusi in alcun modo.
        <br/>I suoi dati personali potranno inoltre essere trasferiti, limitatamente alle finalità sopra riportate, nei seguenti stati: Italia.
        <br/><span style="font-family:'helveticamedium'">Periodo di Conservazione</span>
        <br/>Le segnaliamo che, nel rispetto dei principi di liceità, limitazione delle finalità e minimizzazione dei dati, ai sensi dell’art. 5 del GDPR, il periodo di conservazione dei Suoi dati personali è: 5-10 anni, visti gli art. 2948 codice civile che prevede la prescrizione di 5 anni per i pagamenti periodici; art. 2220 codice civile che prevede la conservazione per 10 anni delle scritture contabili; art. 22 del D.P.R. 29 Settembre 1973, n.600.
        <br/><span style="font-family:'helveticamedium'">Misure di sicurezza</span>
        <br/>Tenendo conto dello stato dell’arte e dei costi di attuazione, nonché della natura, del l’oggetto, del contesto e delle finalità del trattamento, come anche del rischio di varia probabilità e gravità per i diritti e le libertà delle persone fisiche, il titolare del trattamento e il responsabile del trattamento mettono in atto misure tecniche e organizzative adeguate per garantire un livello di sicurezza adeguato al rischio connesso al trattamento.
        <br/><span style="font-family:'helveticamedium'">Diritti dell’Interessato</span>
        <br/>1) L’interessato ha diritto di ottenere la conferma dell’esistenza o meno di dati personali che lo riguardano, anche se non ancora registrati, e la loro comunicazione in forma intelligibile
        <br/>2) L’interessato ha diritto di ottenere l’indicazione:
        <br/><?=$this->nbsp(4)?>a) dell’origine dei dati personali;
        <br/><?=$this->nbsp(4)?>b) delle finalità e modalità del trattamento;
        <br/><?=$this->nbsp(4)?>c) della logica applicata in caso di trattamento effettuato con l’ausilio di strumenti elettronici;
        <br/><?=$this->nbsp(4)?>d) degli estremi identificativi del rappresentante designato ai sensi dell’articolo 5, comma 2;
        <br/><?=$this->nbsp(4)?>e) dei soggetti o delle categorie di soggetti ai quali i dati personali possono essere comunicati o che possono venirne a conoscenza in qualità di
        <br/><?=$this->nbsp(8)?>rappresentante designato nel territorio dello Stato, di responsabili o incaricati.
        <br/>3) L’interessato ha diritto di ottenere:
        <br/><?=$this->nbsp(4)?>a) l’aggiornamento, la rettificazione ovvero, quando vi ha interesse, l’integrazione dei dati;
        <br/><?=$this->nbsp(4)?>b) la cancellazione, la trasformazione in forma anonima o il blocco dei dati trattati in violazione di legge, compresi quelli di cui non è necessaria la
        <br/><?=$this->nbsp(8)?>conservazione in relazione agli scopi per i quali i dati sono stati raccolti o successivamente trattati;
        <br/><?=$this->nbsp(4)?>c) l’attestazione che le operazioni di cui alle lettere a) e b) sono state portate a conoscenza, anche per quanto riguarda il loro contenuto, di coloro ai quali
        <br/><?=$this->nbsp(8)?>i dati sono stati comunicati o diffusi, eccettuato il caso in cui tale adempimento si rivela impossibile o comporta un impiego di mezzi manifestamente
        <br/><?=$this->nbsp(8)?>sproporzionato rispetto al diritto tutelato;
        <br/><?=$this->nbsp(4)?>d) la portabilità dei dati.
        <br/>4) L’interessato ha diritto di opporsi, in tutto o in parte:
        <br/><?=$this->nbsp(4)?>a) per motivi legittimi al trattamento dei dati personali che lo riguardano, ancorché pertinenti allo scopo della raccolta;
        <br/><?=$this->nbsp(4)?>b) al trattamento di dati personali che lo riguardano a fini di invio di materiale pubblicitario o di vendita diretta o per il compimento di ricerche di mercato
        <br/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;o di comunicazione commerciale.
        <br/>Per esercitare i propri diritti o per ottenere le informazioni relative all’eventuale trasferimento dei Suoi dati verso un Paese terzo, potrà formulare espressa richiesta scritta da inviarsi all’Ufficio Tesseramento di MULTIPOPOLARE APS all’indirizzo di posta elettronica:
        <br/><span style="font-family:'helveticamedium'">organizzazione@multipopolare.it</span>
        <br/>Fatto salvo ogni altro ricorso amministrativo o giurisdizionale, Lei ha il diritto di proporre reclamo a un’Autorità di controllo, qualora ritenga che il trattamento che La riguarda violi il GDPR.
        <br/><span style="font-family:'helveticamedium'">Titolare e responsabili del trattamento</span>
        <br/>Titolare del trattamento dei dati personali è MULTIPOPOLARE APS, e-mail: <span style="font-family:'helveticamedium'">organizzazione@multipopolare.it</span>
        <br/>
        <br/><span style="font-family:'helveticamedium'">IL PRESENTE MODULO VA COMPILATO E FIRMATO IN OGNI SUA PARTE E CARICATO TRAMITE L'APPOSITO MODULO SUL SITO multipopolare.it O SPEDITO PER MAIL ALL'INDIRIZZO organizzazione@multipopolare.it</span>
    </p>
<?php
$pdf->WriteHTML(ob_get_clean(), true, false, true);