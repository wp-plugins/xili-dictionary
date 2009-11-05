// xili-dictionary plugin
// used for plural add or edit meta-box
// v 1.0.1 - © 20091105 - dev.xiligroup.com
//
var $j = jQuery;
jQuery(document).ready(function($) {
		$('#btnAdd').click(function() {
			$aa = new Array ('');
			addanelement ($aa,0);
			calculateSum();	
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
		
		
		var descriptioncontent = $("textarea#dictioline_description").val();
		var plurals = descriptioncontent.split('[XPLURAL]');
		if (plurals.length > 1) {
			var howtoadd = plurals.length - 1;
			$('#dictioline_description1').val(plurals[0]);
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
				sum += '[XPLURAL]' + this.value ;
			}

	});
	$j("textarea#dictioline_description").val(sum);
}
		