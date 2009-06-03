{if $courselevelexport}<?xml version="1.0" encoding="UTF-8"?>{/if}
<assessmentItem xmlns="http://www.imsglobal.org/xsd/imsqti_item_v2p0" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.imsglobal.org/xsd/imsqti_item_v2p0 ./imsqti_item_v2p0.xsd" identifier="{$assessmentitemidentifier}" title="{$assessmentitemtitle}" adaptive="false" timeDependent="false">
	<responseDeclaration identifier="{$questionid}" cardinality="single" baseType="string"/>
	<outcomeDeclaration identifier="SCORE" cardinality="single" baseType="integer"/>
	<itemBody>
	    <div class="assesmentItemBody">
    		<p>{$questionText}</p>
		</div>
		<div class="interactive.extendedText">
			<extendedTextInteraction responseIdentifier="{$questionid}" expectedLength="600">
			</extendedTextInteraction>
		</div>
	{if $question_has_image == 1}
		<div class="media">
	    {if $hassize == 1}
			<object type="{$question->mediamimetype}" data="{$question->mediaurl}" width="{$question->mediax}" height="{$question->mediay}" />
		{else}
			<object type="{$question->mediamimetype}" data="{$question->mediaurl}" />     
		{/if}
		</div>
	{/if}
	</itemBody>
{if $question->feedback != ''}
	<modalFeedback outcomeIdentifier="FEEDBACK" identifier="{$questionid}" showHide="hide">{$question->feedback}</modalFeedback>
	<modalFeedback outcomeIdentifier="FEEDBACK" identifier="{$questionid}" showHide="show">{$question->feedback}</modalFeedback>
{/if}
</assessmentItem>
