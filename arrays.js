//
// arrays.js - part of the fq engine
// (c)2000-2002 Cal Henderson and Elliot Brady
//
// Array extensions
//

//
// array.shift removes the first element of an array and returns it
//

if (typeof(Array.shift) == 'undefined'){
	Array.prototype.shift = function ArrayShift(){
		var first = this[0];
		for (var i=0; i<this.length-1; i++){
			this[i] = this[i+1];
		}
		this.length--;
		return first;
	};
}

//
// array.push adds an element to the end of the array
//

if (typeof(Array.push) == 'undefined'){
	Array.prototype.push = function ArrayPush(){
		for (var i=0; i <arguments.length; i++){
			this[this.length] = arguments[i];
		}
	};
}

//
// array.remove removes an indexed element of an array and returns it
//

if (typeof(Array.remove) == 'undefined'){
	Array.prototype.remove = function ArrayRemove(a){
		var item = this[a];
		for (var i=a; i<this.length-1; i++){
			this[i] = this[i+1];
		}
		this.length--;
		return item;
	};
}

//
// array.pop removes an element from the end of the array
//

if (typeof(Array.pop) == 'undefined'){
	Array.prototype.pop = function ArrayPop(){
		var result = this[this.length-1];
		this.length--;
		return result;
	};
}
