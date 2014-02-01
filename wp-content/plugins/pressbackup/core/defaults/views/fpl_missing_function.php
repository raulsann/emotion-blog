<style>
	.wrap p {font-size:14px;}
	.wrap p b {font-size:17px;}
	.wrap pre {float:left; width:15px; background: #969696; color:#fff; font-family: Consolas,Monaco,monospace; padding: 5px; border-radius: 5px 0px 0px 5px; font-size: 18px; line-height: 30px;}
	.wrap pre.code {width:600px;  border-radius: 0px 5px 5px 0px; background: #2E3436;}
	.wrap pre b {color: #90EE90; font-weight: bold; font-size:20px;}
</style>


<div class="wrap">
	<h2>Missing function</h2>
	<p>Function <b><?php echo $fileFunctionName; ?></b> not found</p>
	<p>Add it to <b><?php echo $fileRelativePath;  ?></b></p>



<h2>Example</h2>
<pre>
1
2
3
4
5
6
7
</pre>
<pre class="code">
&lt;?php
	Class <?php echo $fileClassName; ?> { 
		function <b><?php echo $fileFunctionName; ?></b> () {
			//Magic
		}
	}
?&gt;
</pre>
<div style="clear:both;"></div>

</div>
