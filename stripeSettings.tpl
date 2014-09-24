{include file="~widgets/billboard.tpl"}

{$X="X{$Xtra|strtoupper}"}
{$i = $LANG.$X.$method.input}

{include file="~widgets/ajax.form.tpl" input=$i}