/*
Copyright (c) 2010, Yahoo! Inc. All rights reserved.
Code licensed under the BSD License:
http://developer.yahoo.com/yui/license.html
version: 3.2.0
build: 2676
*/
YUI.add("datasource-io",function(B){var A=function(){A.superclass.constructor.apply(this,arguments);};B.mix(A,{NAME:"dataSourceIO",ATTRS:{io:{value:B.io,cloneDefaultValue:false},ioConfig:{value:null}}});B.extend(A,B.DataSource.Local,{initializer:function(C){this._queue={interval:null,conn:null,requests:[]};},_queue:null,_defRequestFn:function(G){var F=this.get("source"),H=this.get("io"),D=this.get("ioConfig"),E=G.request,C=B.merge(D,G.cfg,{on:B.merge(D,{success:function(K,I,J){delete B.DataSource.Local.transactions[J.tId];this.fire("data",B.mix({data:I},J));if(D&&D.on&&D.on.success){D.on.success.apply(D.context||B,arguments);}},failure:function(K,I,J){delete B.DataSource.Local.transactions[J.tId];J.error=new Error("IO data failure");this.fire("data",B.mix({data:I},J));if(D&&D.on&&D.on.failure){D.on.failure.apply(D.context||B,arguments);}}}),context:this,"arguments":G});if(B.Lang.isString(E)){if(C.method&&(C.method.toUpperCase()==="POST")){C.data=C.data?C.data+E:E;}else{F+=E;}}B.DataSource.Local.transactions[G.tId]=H(F,C);return G.tId;}});B.DataSource.IO=A;},"3.2.0",{requires:["datasource-local","io"]});