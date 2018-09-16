/*
* @Author: Yankj
* @Date:   2018-01-07 11:32:23
* @Last Modified by:   Yankj
* @Last Modified time: 2018-01-07 12:37:32
*/

'use strict';

{
  let a = 10;
  var b = 1;
  console.log(a);//10
  console.log(b);//1
}
console.log(a);//ReferenceError: a is not defined
console.log(b);



//作用域

/*let a=0;
function fn(){
    console.log(a,'inner');
    if(false){
        let a =1;
         console.log(a,'if');//0 "inner"
    }
}
console.log(a);//0
fn()*/

/*
var a= 0;
function fn(){
    console.log(a,'inner')//undefined "inner"
    if(false){
        var a =1;
    }
}
console.log(a);//0
fn()*/


/**
let b = 0;
function fn(){
    if(true){
        let b = 1;
         console.log(b,'if');//1,if
    }
    console.log(b,'inner');//0,inner
}
console.log(b);//0
fn()*/
