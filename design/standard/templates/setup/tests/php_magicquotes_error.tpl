<h3>{$result_number}. {"PHP option"|i18n("design/standard/setup/tests")} <i>Magic Quotes</i> {"is enabled"|i18n("design/standard/setup/tests")}</h3>

<p>
 {"eZ publish will work with this option on however it will lead to some minor performance issues since all input variables need to be be converted back to"|i18n("design/standard/setup/tests")} <i>{"normal"|i18n("design/standard/setup/tests")}</i>.
</p>
<p>
 {"It's recommended that the option is turned off. To turn it off edit your"|i18n("design/standard/setup/tests")} <i>php.ini</i> {"configuration and set"|i18n("design/standard/setup/tests")} <i>magic_quotes_gpc</i> {"and"|i18n("design/standard/setup/tests")} <i>magic_quotes_runtime</i> {"to"|i18n("design/standard/setup/tests")} <i>0</i>.
 {"More information on the subject can be found at"|i18n("design/standard/setup/tests")} <a href="http://www.php.net/manual/en/ref.info.php#ini.magic-quotes-gpc">php.net</a>.
</p>
<label>{"Configuration example:"|i18n("design/standard/setup/tests")}</label><br/>
<pre class="example">
magic_quotes_gpc = 0
magic_quotes_runtime = 0
</pre>
<p>
{"Alternatively you may create a file called %1 in your eZ publish root folder and add the following:"|i18n("design/standard/setup/tests",,array(concat("<i>",".htaccess","</i>")))}
</p>
<label>{".htaccess example:"|i18n("design/standard/setup/tests")}</label><br/>
<pre class="example">
php_value magic_quotes_gpc 0
php_value magic_quotes_runtime 0
</pre>
