<?php /** * @since 1.0.2 insert var and constant */
$xplural = $_GET['varp'];
$xctxt = $_GET['varc'];

?>
// xili-dictionary plugin
// used for plural add or edit meta-box
// v 1.0.3 - © 20111108 - dev.xiligroup.com
//
var $j = jQuery;
// init
jQuery(document).ready(function($) {
	$('#btnAdd').click(function() {
		$aa = new Array ('');
		addanelement ($aa,0);
		calculateSum();	
	});
	
	$('#btnCtxt').click(function() {
		if ( $('#context_msgid').css('visibility') == "hidden" ) {
			$('#context_msgid').css('visibility', "visible" );
			$('#for_context_msgid').css('visibility', "visible" );
			var $valbbt = $('#bbt_no').val();
			$('#btnCtxt').val($valbbt);
		} else {
			$('#context_msgid').val('');
			$('#context_msgid').css('visibility', "hidden" );
			$('#for_context_msgid').css('visibility', "hidden" );
			var $valbbt = $('#bbt_add').val();
			$('#btnCtxt').val($valbbt);
		}	
	});
			
	$('#btnDel').click(function() {
		var num	= $('.clonedInput').length;
		$('#input' + num).remove();
		$('#btnAdd').attr('disabled','');
		if (num-1 == 1) {				
			$('#btnDel').attr('disabled','disabled');
			if ($('#dictioline_lang').val() != "")	
					$('#areatitle1').html('Singular (msgstr)');
		}
		calculateSum();
		$('#termnblines').val(num-1);
	});
	$('#btnDel').attr('disabled','disabled');
	
	if ($("textarea#dictioline_description").length > 0) {		
		var descriptioncontent = $("textarea#dictioline_description").val();
		var contexts = descriptioncontent.split('<?php echo $xctxt ?>');
		if (contexts.length > 1) {
			var plurals = contexts[1].split('<?php echo $xplural ?>');
			var context = contexts[0];
		} else {
			var plurals = descriptioncontent.split('<?php echo $xplural ?>');
		}
		$('#dictioline_description1').val(plurals[0]);
		if (plurals.length > 1) {
			var howtoadd = plurals.length - 1;
			for (var x = 1; x <= howtoadd; x++) {
				addanelement (plurals,x);
			}
			calculateSum();
		}
		
		$(".plural").each(function() {
				$(this).keyup(function(){
					calculateSum();
				});
		});	
	}
	$('#btinsert').click(function() {
			var descriptioncontent = $("#originalmsgid").val();
			var contexts = descriptioncontent.split('<?php echo $xctxt ?>');
			
			if (contexts.length > 1) {
				var plurals = contexts[1].split('<?php echo $xplural ?>');
				var context = contexts[0];
			} else {
				var plurals = descriptioncontent.split('<?php echo $xplural ?>');
			}
			$('#dictioline_description1').val(plurals[0]);
			if (plurals.length > 1) {
			var howtoadd = plurals.length - 1;
			for (var x = 1; x <= howtoadd; x++) {
				addanelement (plurals,x);
			}
			calculateSum();
		}
		
		$(".plural").each(function() {
				$(this).keyup(function(){
					calculateSum();
				});
		});
	});	
});
			
function addanelement (plurals,x) {
	var num		= $j('.clonedInput').length;
	var newNum	= new Number(num + 1);
	var newElem = $j('#input' + num).clone().attr('id', 'input' + newNum);
	newElem.children('textarea').attr('id', 'dictioline_description' + newNum).attr('name', 'dictioline_description' + newNum).attr('class', 'plural').attr('value', plurals[x]);
	newElem.children('p').attr('id', 'areatitle' + newNum).attr('name', 'areatitle' + newNum);		
	$j('#input' + num).after(newElem);		
	if ($j('#dictioline_lang').val() == "") {
		$j('#areatitle' + newNum).html('Plural (msgid_plural)');
		$j('#areatitle1').html('Singular (msgid)');
	} else {
		$j('#areatitle' + newNum).html('Plural (msgstr['+ (newNum-1) +'])');
		$j('#areatitle1').html('Singular (msgstr[0])');
	}		
	$j('#btnDel').attr('disabled','');	
	if (($j('#dictioline_lang').val() == "" && newNum == 2) || ($j('#dictioline_lang').val() != "" && newNum == 4))
			$j('#btnAdd').attr('disabled','disabled');
	$j(".plural").each(function() {
		$j(this).keyup(function(){
		calculateSum();
		});
	});
	$j('#termnblines').val(newNum);
}
								
function calculateSum($) {
	var sum = "";
	$j(".plural").each(function($) {
			if (sum == "") {
				sum += this.value ;
			} else {
				sum += '<?php echo $xplural ?>' + this.value ;
			}
	});
	sum = $j().val('#context_msgid') + sum ;
	$j("textarea#dictioline_description").val(sum);
}
<?php /* end plural javascript containing php vars */ ?>		