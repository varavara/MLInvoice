<?php
/*******************************************************************************
VLLasku: web-based invoicing application.
Copyright (C) 2010 Ere Maijala

Portions based on:
PkLasku : web-based invoicing software.
Copyright (C) 2004-2008 Samu Reinikainen

This program is free software. See attached LICENSE.

*******************************************************************************/

/*******************************************************************************
VLLasku: web-pohjainen laskutusohjelma.
Copyright (C) 2010 Ere Maijala

Perustuu osittain sovellukseen:
PkLasku : web-pohjainen laskutusohjelmisto.
Copyright (C) 2004-2008 Samu Reinikainen

T�m� ohjelma on vapaa. Lue oheinen LICENSE.

*******************************************************************************/

require "htmlfuncs.php";
require "sqlfuncs.php";
require "sessionfuncs.php";

$strSesID = sesVerifySession();


require "localize.php";

require "datefuncs.php";
require "miscfuncs.php";

$intInvoiceId = getRequest('id', FALSE);
$boolRefund = getRequest('refund', FALSE);

echo htmlPageStart( _PAGE_TITLE_ );

if ($intInvoiceId) 
{
    if ($boolRefund)
    {
      $strQuery = "UPDATE {prefix}invoice " .
        "SET state_id = 4 " .
        "WHERE {prefix}invoice.id = ?";
      mysql_param_query($strQuery, array($intInvoiceId));
    }

    $strQuery = 
        "SELECT * ".
        "FROM ". _DB_PREFIX_. "_invoice ".
        "WHERE ". _DB_PREFIX_. "_invoice.id = $intInvoiceId";
    $intRes = mysql_query($strQuery);
    if ($row = mysql_fetch_assoc($intRes)) {
        $strname = $row['name'];
        $intCompanyId = $row['company_id'];
        $intInvoiceNo = $row['invoice_no'];
        $intRealInvoiceNo = $row['real_invoice_no'];
        $intInvoiceDate = $row['invoice_date'];
        $intDueDate = $row['due_date'];
        $intPaymentDate = $row['payment_date'];
        $intRefNumber = $row['ref_number'];
        $intStateId = $row['state_id'];
        $strReference = $row['reference'];
        $intBaseId = $row['base_id'];
    }
    
    $intDate = date("Ymd");
    $intDueDate = date("Ymd",mktime(0, 0, 0, date("m"), date("d") + $paymentDueDate, date("Y")));
    
    $intNewInvNo = 0;
    $intNewRefNo = 'NULL';
    if ($addInvoiceNumber || $addReferenceNumber)     
    {
      $strQuery = "SELECT max(invoice_no) FROM {prefix}invoice";
      $intRes = mysql_query_check($strQuery);
      $intInvNo = mysql_result($intRes, 0, 0) + 1;
      if ($addInvoiceNumber)
        $intNewInvNo = $intInvNo;
      if ($addReferenceNumber)
        $intNewRefNo = $intInvNo . miscCalcCheckNo($intInvNo);
    }
    
    $intRefundedId = $boolRefund ? $intInvoiceId : 'NULL';
    $strQuery = 
        "INSERT INTO {prefix}invoice(name, company_id, invoice_no, real_invoice_no, invoice_date, due_date, payment_date, ref_number, state_id, reference, base_id, refunded_invoice_id) ".
        "VALUES(?, ?, ?, 0, ?, ?, NULL, ?, 1, ?, ?, ?)";
        
    mysql_param_query($strQuery, array($strname, $intCompanyId, $intNewInvNo, $intDate, $intDueDate, $intNewRefNo, $strReference, $intBaseId, $intRefundedId));
    $intNewId = mysql_insert_id();
    if( $intNewId ) {    
        $strQuery = 
            "SELECT * ".
            "FROM {prefix}invoice_row ".
            "WHERE invoice_id = ?";
        $intRes = mysql_param_query($strQuery, array($intInvoiceId));
        while ($row = mysql_fetch_assoc($intRes)) {
            $intProductId = $row['product_id'];
            $strDescription = $row['description'];
            $intTypeId = $row['type_id'];
            $intPcs = $row['pcs'];
            $intPrice = $row['price'];
            $intRowDate = $row['row_date'];
            $intVat = $row['vat'];
            $intOrderNo = $row['order_no'];
            $boolVatIncluded = $row['vat_included'];
            $intReminderRow = $row['reminder_row'];

            if ($boolRefund)
              $intPcs = -$intPcs;
            else if ($intReminderRow)
              continue;
            
            $strQuery = 
                "INSERT INTO {prefix}invoice_row(invoice_id, product_id, description, type_id, pcs, price, row_date, vat, order_no, vat_included, reminder_row) ".
                "VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $intRes = mysql_param_query($strQuery, array($intNewId, $intProductId, $strDescription, $intTypeId, $intPcs, $intPrice, $intRowDate, $intVat, $intOrderNo, $boolVatIncluded, $intReminderRow));
        }
    }
}

$strLink = "form.php?ses=". $strSesID. "&selectform=invoice&id=". $intNewId. "&key_name=id&refresh_list=1";

?>
<script language="javascript">
<!--
function updateOpener() {
    //alert('<?php echo $GLOBALS['locREMEMBER']?>');
    window.opener.location.href='<?php echo $strLink?>';
    self.close();
    return 1;
}
-->
</script>

<body class="navi" onload="updateOpener();">

<?php echo $GLOBALS['locMAYCLOSE']?>

</body>
</html>