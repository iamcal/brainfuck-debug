<html>
<head>
<title>Javascript Brainfuck Interpreter / Debugger</title>
<script language="Javascript" src="arrays.js"></script>
<script>

// interactive input mode:
// 
// a     ascii char
// #97   decimal char index
// !141  octal char index
// $61   hex char index

var g_debugging = 0;
var g_memory = Array();
var g_max_mem = 255;
var g_max_val = 255;
var g_ip = 0;
var g_mp = 0;
var g_dp = 0;
var g_program = new Array();
var g_targets = new Array();
var g_input = new Array();
var g_output = '';
var g_viewer_width = 60;
var g_quit_debug_run = 0;
var g_debugging_running = 0;
var g_prompt_for_input = 0;
var g_running = 0;
var g_linebreaker = "\n";

function init(){
	if (navigator.userAgent.toLowerCase().indexOf("msie") != -1){
		g_linebreaker = "\r";
	}

    document.addEventListener('keypress', function(e) {
        //n for single step
        if (e.keyCode == 110)
            document.getElementById('button_step').click();
        //c for run to next breakpoint
        else if (e.keyCode == 99)
            document.getElementById('button_run_debug').click();
    }, false);
}

function init_memory(){
	for(var i=0; i<=g_max_mem; i++){
		g_memory[i] = 0;
	}
	g_mp = 0;
}

function init_io(){
	g_dp = 0;
	g_output = '';
}

function init_prog(code){
	g_program.length = 0;
	for(var i=0; i<code.length; i++){
		var op = code.charAt(i)
		// check it's not a carriage return or anything that will
		//  break the program viewer too badly :)
		if (is_valid_op(op)){
			g_program[g_program.length] = op;
		}
	}	
	g_ip = 0;
	init_targets();
}

function init_targets(){
	g_targets.length = 0;
	var temp_stack = new Array();
	for(var i=0; i<g_program.length; i++){
		var op = g_program[i];
		if (op == '['){
			temp_stack.push(i);
		}
		if (op == ']'){
			if (temp_stack.length == 0) alert('Parseing error: ] with no matching [');
			var target = temp_stack.pop();
			g_targets[i] = target;
			g_targets[target] = i;
		}
	}
	if (temp_stack.length > 0) alert('Parseing error: [ with no matching ]');
}

function init_input(){
	g_prompt_for_input = document.getElementById('input_mode_1').checked;
	g_input.length = 0;
	var in_data = document.getElementById('edit_input').value;
	for(var i=0; i<in_data.length; i++){
		g_input[g_input.length] = in_data.charAt(i);
	}	
	g_dp = 0;
}

function get_input(){
	if (g_prompt_for_input){
		var data = window.prompt("Enter an input character (use #xxx to specify a decimal code, !xxx for an octal code, or $xxx for a hex code):", "#0");
		if ((data == null) || (!data)) return 0;
		if (data.charAt(0) == '#'){
			return parseInt(data.substr(1), 10);
		}
		if (data.charAt(0) == '!'){
			return eval('0'+data.substr(1));
		}
		if (data.charAt(0) == '$'){
			return eval('0x'+data.substr(1));
		}
		return data.charCodeAt(0);
	}else{
		var result = (g_dp >= g_input.length)?0:g_input[g_dp].charCodeAt(0);
		g_dp++;
		return result;
	}
}

function is_valid_op(op){
	if (op == '+') return 1;
	if (op == '-') return 1;
	if (op == '>') return 1;
	if (op == '<') return 1;
	if (op == '[') return 1;
	if (op == ']') return 1;
	if (op == '.') return 1;
	if (op == ',') return 1;
	if (op == '#') return 1;
	return 0;
}

function put_output(c){
	g_output += c;
}

function execute_opcode(op){
	switch(op){
		case '+':
			g_memory[g_mp]++;
			if (g_memory[g_mp] > g_max_val) g_memory[g_mp] = 0;
			break;
		case '-':
			g_memory[g_mp]--;
			if (g_memory[g_mp] < 0) g_memory[g_mp] = g_max_val;
			break;
		case '>':
			g_mp++;
			if (g_mp > g_max_mem) g_mp = 0;
			break;
		case '<':
			g_mp--;
			if (g_mp < 0) g_mp = g_max_mem;
			break;
		case '[':
			if (g_memory[g_mp] == 0) g_ip = g_targets[g_ip];
			break;
		case ']':
			g_ip = g_targets[g_ip] - 1;
			break;
		case '.':
			put_output(String.fromCharCode(g_memory[g_mp]));
			break;
		case ',':
			g_memory[g_mp] = get_input();
			break;
	}
}

function bf_interpret(prog, input){

	if (g_running){
		bf_stop_run();
		return;
	}
	g_running = 1;

	init_prog(prog);
	init_memory();
	init_io();
	init_input();

	disable_text_box('edit_source');
	disable_text_box('edit_input');
	disable_text_box('edit_output');
	disable_text_box('edit_progs');
	disable_button('input_mode_1');
	disable_button('input_mode_2');
	disable_button('button_debug');
	change_button_caption('button_run', 'Stop');

	bf_run_step();
}

function bf_stop_run(){
	enable_text_box('edit_source');
	enable_text_box('edit_input');
	enable_text_box('edit_output');
	enable_text_box('edit_progs');
	enable_button('input_mode_1');
	enable_button('input_mode_2');
	enable_button('button_debug');
	change_button_caption('button_run', 'Run');
	sync_input();

	g_running = 0;
}

function bf_run_done(){
	bf_stop_run();
	document.getElementById('edit_output').value = g_output;
}

function bf_run_step(){
	// execute instrcution under ip
	var op = g_program[g_ip];

	execute_opcode(op);

	// increment ip
	g_ip++;

	if (g_ip >= g_program.length){
		bf_run_done();
		return;
	}

	window.setTimeout('bf_run_step();', 0);
}

function update_memview(){
	var mem_slots = Math.floor(g_viewer_width / 4);
	var pre_slots = Math.floor(mem_slots / 2);
	var low_slot = g_mp - pre_slots;
	if (low_slot < 0) low_slot += g_max_mem;

	var line_1 = '';
	for(var i=0; i<mem_slots; i++){
		var slot = low_slot + i;
		if (slot >= g_max_mem) slot -= g_max_mem;
		var label = pad_num(g_memory[slot], 3);
		line_1 += label + ' ';
	}

	var line_2 = '';
	for(var i=0; i<pre_slots; i++){
		line_2 += '    ';
	}
	line_2 += '^';

	var line_3 = '';
	for(var i=0; i<pre_slots; i++){
		line_3 += '    ';
	}
	line_3 += 'mp='+g_mp;

	var line_4 = '';
	for(var i=0; i<mem_slots; i++){
		var slot = low_slot + i;
		if (slot >= g_max_mem) slot -= g_max_mem;
		var label = pad_num(slot, 3);
		line_4 += label + ' ';
	}

	set_viewdata('memview', line_1 + g_linebreaker + line_2 + g_linebreaker + line_3 + g_linebreaker + line_4);
}

function pad_num(a, b){
	var c = new String(a);
	for(var i=c.length; i<b; i++) c = '0'+c;
	return c;
}

function update_progview(){
	var pre_slots = Math.floor(g_viewer_width / 2);
	var low_slot = g_ip - pre_slots;

	var line_1 = '';
	for(var i=0; i<g_viewer_width; i++){
		var slot = low_slot + i;
		if ((slot >= 0) && (slot < g_program.length)){
			line_1 += g_program[slot];
		}else{
			line_1 += '_';
		}
	}

	var line_2 = '';
	for(var i=0; i<pre_slots; i++){
		line_2 += ' ';
	}
	line_2 += '^';

	var line_3 = '';
	for(var i=0; i<pre_slots; i++){
		line_3 += ' ';
	}
	line_3 += 'ip='+g_ip;

	set_viewdata('progview', line_1 + g_linebreaker + line_2 + g_linebreaker + line_3);
}

function update_inputview(){
	if (g_prompt_for_input){
		set_viewdata('inputview', "-input prompt mode-");
	}else{
		var line_1 = g_input.join('');
		var line_2 = '';
		for (var i=0; i<g_dp; i++) line_2 += ' ';
		line_2 += '^';
		set_viewdata('inputview', line_1 + g_linebreaker + line_2);
	}
}

function update_outputview(){
	var line_1 = g_output;
	var line_2 = '';
	for (var i=0; i<g_output.length; i++) line_2 += ' ';
	line_2 += '^';
	set_viewdata('outputview', line_1 + g_linebreaker + line_2);
}

function set_viewdata(view, data){
	var new_node = document.createTextNode(data);
	var p_node = document.getElementById(view);
	p_node.replaceChild(new_node, p_node.childNodes[0]);
}

function run(f){
	bf_interpret(f.source.value, f.input.value);
}

function debug_done(){
	disable_button('button_step');
	disable_button('button_run_debug');	
}

function debug_toggle(f){
	if (g_debugging == 1){
		g_debugging = 0;
		enable_text_box('edit_source');
		enable_text_box('edit_input');
		enable_text_box('edit_output');
		enable_text_box('edit_progs');
		enable_button('button_run');
		enable_button('input_mode_1');
		enable_button('input_mode_2');
		change_button_caption('button_debug', 'Start Debugger');
		disable_button('button_step');
		disable_button('button_run_debug');
		set_viewdata('progview', ' ');
		set_viewdata('memview', ' ');
		set_viewdata('inputview', ' ');
		set_viewdata('outputview', ' ');
		sync_input();
	}else{
		g_debugging = 1;
		disable_text_box('edit_source');
		disable_text_box('edit_input');
		disable_text_box('edit_output');
		disable_text_box('edit_progs');
		disable_button('button_run');
		disable_button('input_mode_1');
		disable_button('input_mode_2');
		change_button_caption('button_debug', 'Quit Debugger');
		enable_button('button_step');
		enable_button('button_run_debug');
		start_debugger();
	}
}

function start_debugger(){
	init_memory();
	init_io();
	init_prog(document.getElementById('edit_source').value);
	init_input();
	update_memview();
	update_progview();
	update_inputview();
	update_outputview();
}

function run_step(){
	var op = g_program[g_ip];
	execute_opcode(op);
	g_ip++;
	update_memview();
	update_progview();
	update_inputview();
	update_outputview();
	
	if (g_ip >= g_program.length){
		debug_done();
		//alert("done!");
	}
}

function start_debug_run(){
	disable_button('button_debug');
	disable_button('button_step');
	change_button_caption('button_run_debug', 'Stop Running (c)');
	g_debugging_running = 1;
}

function stop_debug_run(){
	enable_button('button_debug');
	enable_button('button_step');
	change_button_caption('button_run_debug', 'Run To Breakpoint (c)');
	g_debugging_running = 0;
}

function run_debug(){
	if (g_debugging_running){
		g_quit_debug_run = 1;
	}else{
		start_debug_run();
		g_quit_debug_run = 0;
		run_debug_step();
	}
}

function run_debug_step(){
	run_step();
	if ((g_program[g_ip] == '#') || g_quit_debug_run || (g_ip >= g_program.length)){
		stop_debug_run();
		if (g_ip >= g_program.length){
			debug_done();
		}
		return;
	}
	window.setTimeout('run_debug_step();', 0);
}

function disable_text_box(name){
	var elm = document.getElementById(name);
	elm.disabled = true;
	elm.style.backgroundColor = '#cccccc';
}

function enable_text_box(name){
	var elm = document.getElementById(name);
	elm.disabled = false;
	elm.style.backgroundColor = '';
}

function disable_button(name){
	var elm = document.getElementById(name);
	elm.disabled = true;
}

function enable_button(name){
	var elm = document.getElementById(name);
	elm.disabled = false;
}

function change_button_caption(name, caption){
	var elm = document.getElementById(name);
	elm.value = caption;
}

function sync_input(){
	if (document.getElementById('input_mode_1').checked){
		disable_text_box('edit_input');
	}else{
		enable_text_box('edit_input');
	}
}

</script>
<style>

body {
	margin: 0px;
	background-color: #eeeeee;
	color: #000000;
	font-family: Arial, Helvetica, Sans-serif;
}

textarea.editsmall {
	width: 400px;
	height: 100px;
}

textarea.edit {
	width: 400px;
	height: 200px;
}

div.main {
	padding: 6px;
	border: 1px solid #000000;
	background-color: #dddddd;
}

pre.viewer {
	width: 500px;
	padding: 6px;
	border: 1px solid #000000;
	background-color: #dddddd;
	margin: 0px;
}

</style>
</head>
<body onload="init();">

<? include('/var/www/cal/iamcal.com/templates/universal_nav.txt'); ?>

<div id="page">

<form name="mainform">

<table cellspacing="20" cellpadding="0" border="0" align="center">
	<tr valign="top">
		<td colspan="2">
			<h1>Javascript Brainfuck Interpreter / Debugger</h1>
			<b>By <a href="http://www.iamcal.com/">Cal Henderson</a></b><br>
			<hr>
		</td>
	</tr>
	<tr valign="top">
		<td>
			Program:<br>
			<select onchange="this.form.source.value=this.options[this.selectedIndex].value; this.selectedIndex=0;" id="edit_progs">
				<option value="">Example programs...</option>
				<option value="++++++++++[>+++++++>++++++++++>+++>+<<<<-]>++.>+.+++++++..+++.>++.<<+++++++++++++++.>.+++.------.--------.>+.>.">Hello World</option>
				<option value=",[>+<-[>+<-[>+<-[>+<-[>+<-[>+<-[>+<-[>+<-[>+<-[>+<-[>+<-[>+<-[>+<-[>+<-[>+<-[>+<-[>+<-[>+<-[>+<-[>+<-[>+<-[>+<-[>+<-[>+<-[>+<-[>+<-[>+<-[>+<-[>+<-[>+<-[>+<-[>+<-[>+<-[>+<-[>+<-[>+<-[>+<-[>+<-[>+<-[>+<-[>+<-[>+<-[>+<-[>+<-[>+<-[>+<-[>+<-[>+<-[>+<-[>+<-[>+<-[>+<-[>+<-[>+<-[>+<-[>+<-[>+<-[>+<-[>+<-[>+<-[>+<-[>+<-[>+<-[>+<-[>++++++++++++++<-[>+<-[>+<-[>+<-[>+<-[>+<-[>+<-[>+<-[>+<-[>+<-[>+<-[>+<-[>+<-[>>+++++[<----->-]<<-[>+<-[>+<-[>+<-[>+<-[>+<-[>+<-[>+<-[>+<-[>+<-[>+<-[>+<-[>+<-[>++++++++++++++<-[>+<-[>+<-[>+<-[>+<-[>+<-[>++++++++++++++<-[>+<-[>+<-[>+<-[>+<-[>+<-[>+<-[>+<-[>+<-[>+<-[>+<-[>+<-[>+<-[>>+++++[<----->-]<<-[>+<-[>+<-[>+<-[>+<-[>+<-[>+<-[>+<-[>+<-[>+<-[>+<-[>+<-[>+<-[>++++++++++++++<-[>+<-]]]]]]]]]]]]]]]]]]]]]]]]]]]]]]]]]]]]]]]]]]]]]]]]]]]]]]]]]]]]]]]]]]]]]]]]]]]]]]]]]]]]]]]]]]]]]]]]]]]]]]]]]]]]]]]]]]]]]]]]]]]>.[-]<,]">Rot 13 (slow!)</option>
				<option value="+++++++++++>+>>>>++++++++++++++++++++++++++++++++++++++++++++>++++++++++++++++++++++++++++++++<<<<<<[>[>>>>>>+>+<<<<<<<-]>>>>>>>[<<<<<<<+>>>>>>>-]<[>++++++++++[-<-[>>+>+<<<-]>>>[<<<+>>>-]+<[>[-]<[-]]>[<<[>>>+<<<-]>>[-]]<<]>>>[>>+>+<<<-]>>>[<<<+>>>-]+<[>[-]<[-]]>[<<+>>[-]]<<<<<<<]>>>>>[++++++++++++++++++++++++++++++++++++++++++++++++.[-]]++++++++++<[->-<]>++++++++++++++++++++++++++++++++++++++++++++++++.[-]<<<<<<<<<<<<[>>>+>+<<<<-]>>>>[<<<<+>>>>-]<-[>>.>.<<<[-]]<<[>>+>+<<<-]>>>[<<<+>>>-]<<[<+>-]>[<+>-]<<<-]">Fibonacci Numbers</option>
				<option value="++++++++++++++++++++#++++++++++++++++++++#++++++++++++++++++++#++++++++++++++++++++#++++++++++++++++++++#-.--.+++++++++++.">Breakpoints Demo</option>
			</select><br>
			<textarea id="edit_source" name="source" wrap="virtual" class="edit"></textarea><br>
			<br>

			Input:<br>
			<input type="radio" id="input_mode_1" name="input_mode" value="1" onclick="sync_input();"> Prompt for input as needed<br>
			<input type="radio" id="input_mode_2" name="input_mode" value="2" onclick="sync_input();" checked> Pre-supply input:<br>
			<textarea id="edit_input" name="input" wrap="virtual" class="editsmall"></textarea><br>
			<br>

			Output:<br>
			<textarea id="edit_output" name="output" wrap="virtual" class="editsmall"></textarea><br>
			<br>
		</td>
		<td>
			<br>
			<input type="button" value="Run" onclick="run(this.form);" id="button_run">
			<input type="button" value="Start Debugger" onclick="debug_toggle(this.form);" id="button_debug">
			<input type="button" value="Single Step (n)" onclick="run_step();" disabled id="button_step">
			<input type="button" value="Run To Breakpoint (c)" onclick="run_debug();" disabled id="button_run_debug">
			<br>
			<br>

			Source Viewer:<br>
			<pre class="viewer" id="progview"> </pre>
			<br>

			Memory Viewer:<br>
			<pre class="viewer" id="memview"> </pre>
			<br>

			Input Viewer:<br>
			<pre class="viewer" id="inputview"> </pre>
			<br>

			Output Viewer:<br>
			<pre class="viewer" id="outputview"> </pre>
			<br>

			<b>Note:</b> The hash ("#") character marks a breakpoint.
		</td>
	</tr>
	<tr valign="top">
		<td colspan="2">
			Example programs taken from <a href="http://esoteric.sange.fi/brainfuck/">the Brainfuck Archive</a>
		</td>
	</tr>
</table>

</form>

</div>

<? include('/var/www/cal/iamcal.com/templates/universal_tracker.txt'); ?>

</body>
</html>
