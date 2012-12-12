var com = {};
com.elfvision = {};
com.elfvision.kit = {};
com.elfvision.kit.LocationSelect = {};
com.elfvision.ajax = {};
com.elfvision.DEBUG = false;
(function() {
	var e, b, m, p, h, f, a, c, g, r, o, k, l, q, j, i, d, n;
	k = function() {
		if (com.elfvision.DEBUG) {
			var s = new Date().getTime();
			return function() {
				var u = 0, v = arguments.length, t = [ "[DEBUG at ",
						(new Date().getTime() - s), " ] : " ];
				for (; u < v; u++) {
					t.push(arguments[u])
				}
				if (window.console !== undefined
						&& typeof window.console.log == "function") {
					console.log.apply(console, t)
				} else {
				}
			}
		} else {
			return function() {
			}
		}
	}();
	q = function(t, s) {
		return function() {
			s.apply(t, arguments)
		}
	};
	l = function(x, v, u, t) {
		k("attaching event", v, "on the object", x);
		var s = function(y) {
			u.apply(t || x, [ y ])
		}, w;
		if (window.jQuery !== undefined) {
			jQuery(x).bind(v, s)
		} else {
			if (document.addEventListener) {
				x.addEventListener(v, s, false)
			} else {
				if (document.attachEvent) {
					s = function(z) {
						if (!z) {
							z = window.event
						}
						var y = {
							_event : z,
							type : z.type,
							target : z.srcElement,
							currentTarget : x,
							relatedTarget : z.fromElement ? z.fromElement
									: z.toElement,
							eventPhase : (z.srcElement == x) ? 2 : 3,
							clientX : z.clientX,
							clientY : z.clientY,
							screenX : z.screenX,
							screenY : z.screenY,
							altKey : z.altKey,
							ctrlKey : z.ctrlKey,
							shiftKey : z.shiftKey,
							charCode : z.keyCode,
							stopPropagation : function() {
								this._event.cancelBubble = true
							},
							preventDefault : function() {
								this._event.returnValue = false
							}
						};
						u.apply(t || x, [ y ])
					};
					x.attachEvent("on" + v, s)
				}
			}
		}
	};
	c = function() {
		var s, u, t = [ function() {
			return new XMLHttpRequest()
		}, function() {
			return new ActiveXObject("Msxml2.XMLHTTP")
		}, function() {
			return new ActiveXObject("Msxml3.XMLHTTP")
		}, function() {
			return new ActiveXObject("Microsoft.XMLHTTP")
		} ];
		return {
			create : function() {
				if (s) {
					return s()
				}
				for ( var v = 0; v < t.length; v++) {
					try {
						var y = t[v];
						var w = y();
						if (w) {
							s = y;
							return w
						}
					} catch (x) {
						continue
					}
				}
				s = function() {
					throw new Error("XMLHttpRequest not supported")
				};
				s()
			}
		}
	}();
	g = function(s) {
		if (!s.url) {
			throw new Error("getJson : Must provide url for the request!")
		}
		var t = c.create();
		t.onreadystatechange = function() {
			k("Request Object ", t);
			if (t.readyState == 4) {
				if (t.status == 200 || t.status === 0) {
					k("JSON is successfully retrived according to ", s);
					if (s.callback) {
						k("about to parse json");
						var u = JSON.parse(t.responseText);
						k("parsed ", u);
						s.callback.call(this, u)
					}
				}
			}
		};
		t.open("GET", s.url, true);
		t.setRequestHeader("Cache-Control",
				"max-age=0,no-cache,no-store,post-check=0,pre-check=0");
		t.setRequestHeader("Expires", "Mon, 26 Jul 1997 05:00:00 GMT");
		t.send(null)
	};
	r = function(u, s) {
		var t = document.createElement("script"), v, w;
		if (s) {
			v = /callback=(\w+)&*/;
			w = v.exec(u)[1];
			window[w] = function(x) {
				s(x);
				window[w] = null
			}
		}
		t.src = u;
		document.getElementsByTagName("head")[0].appendChild(t)
	};
	o = function(u, t) {
		var s = document.createElement("script");
		s.src = u;
		k("getting script", u);
		if (t) {
			s.onload = t;
			s.onreadystatechange = function() {
				if (s.readyState == 4 || s.readyState == "loaded"
						|| s.readyState == "complete") {
					t()
				}
			}
		}
		document.getElementsByTagName("head")[0].appendChild(s)
	};
	n = function(w, t) {
		if (!Array.prototype.forEach) {
			var s = w.length >>> 0;
			if (typeof t != "function") {
				throw new TypeError()
			}
			var v = arguments[1];
			for ( var u = 0; u < s; u++) {
				if (u in w) {
					t.call(v, w[u], u, this)
				}
			}
		} else {
			return Array.prototype.forEach.call(w, t)
		}
	};
	d = function(u) {
		var v = [], t = [];
		for ( var s in u) {
			t = [];
			t.push(s);
			t.push("=");
			t.push(u[s]);
			v.push(t.join(""))
		}
		return v.join("&")
	};
	i = function(s) {
		if (s && typeof s === "object" && s.constructor === Array) {
			return true
		}
	};
	j = function() {
		this.observers = [];
		this.guid = 0
	};
	j.prototype.subscribe = function(s) {
		var t = this.guid++;
		this.observers[t] = s;
		return t
	};
	j.prototype.unSubscribe = function(s) {
		delete this.observers[s]
	};
	j.prototype.notify = function(t) {
		for ( var u in this.observers) {
			var s = this.observers[u];
			if (s instanceof Function) {
				s.call(this, t)
			} else {
				s.update.call(this, t)
			}
		}
	};
	e = function(s) {
		this.onRowsInserted = new j();
		this.onRowsRemoved = new j();
		this.onRowsUpdated = new j();
		this.onSelectedIndexChanged = new j();
		this.items = [];
		this.selectedIndex = 0;
		this.level = s.level || 0;
		this.label = s.label || "Select..."
	};
	e.prototype.read = function(s) {
		if (s) {
			k("reading items[" + s + "]:", this.items[s]);
			return this.items[s]
		} else {
			return this.items
		}
	};
	e.prototype.insert = function(s) {
		if (i(s)) {
			s = [ s ];
			this.items = this.items.concat(s)
		} else {
			var t = s;
			this.items.push(t)
		}
		this.onRowsInserted.notify({
			source : this,
			items : s
		})
	};
	e.prototype.remove = function(t) {
		var s;
		if (t) {
			n(this.items, function(v, u) {
				if (v.id === t) {
					s = v;
					this.items.splice(u, 1)
				}
			})
		} else {
			this.items = []
		}
		k("notifying removing");
		this.onRowsRemoved.notify({
			source : this,
			items : [ s ]
		})
	};
	e.prototype.update = function(s) {
		s = s || [];
		k("updating list model with ", s);
		this.items = [ {
			id : 0,
			text : this.label
		} ].concat(s);
		k("notifying updating");
		this.onRowsUpdated.notify({
			source : this,
			items : s
		})
	};
	e.prototype.getSelectedIndex = function() {
		return this.selectedIndex
	};
	e.prototype.setSelectedIndex = function(s) {
		var t = this.getSelectedIndex();
		if (t === s) {
			return
		}
		this.selectedIndex = s;
		k("notifying index changed", s);
		this.onSelectedIndexChanged.notify({
			source : this,
			previous : t,
			present : s,
			previousItem : this.read(t),
			presentItem : this.read(s),
			level : this.level
		})
	};
	b = function(u) {
		this.model = u.model;
		this.controller = u.controller;
		this.element = u.element;
		var t = q(this, this.rebuildList), s = q(this.controller.parent,
				this.controller.parent.update);
		this.model.onRowsInserted.subscribe(t);
		this.model.onRowsRemoved.subscribe(t);
		this.model.onRowsUpdated.subscribe(t);
		this.model.onSelectedIndexChanged.subscribe(s);
		k("this list item", this);
		l(this.element, "change", this.controller.updateSelectedIndex,
				this.controller)
	};
	b.prototype.show = function() {
		this.element.style.display = "inline-block"
	};
	b.prototype.hide = function() {
		this.element.style.display = "none"
	};
	b.prototype.rebuildList = function(w) {
		if (w && w.present && w.present === 0) {
			this.elements.list.selectedIndex = 0;
			return
		}
		k("Rebuilding list ", this);
		var v = this.element, s = this.model.read(), u = s.length, t;
		v.innerHTML = "";
		k(s.length);
		n(s, function(y, x) {
			t = new Option();
			t.setAttribute("value", y.id ? y.text : '');
			t.appendChild(document.createTextNode(y.text));
			v.appendChild(t)
		});
		this.model.setSelectedIndex(0)
	};
	m = function(s) {
		this.parent = s.parent;
		this.model = new e({
			level : s.level,
			label : s.label
		});
		this.view = new b({
			model : this.model,
			controller : this,
			element : s.element
		})
	};
	m.prototype.refresh = function(s) {
		k("refresh data with ", s);
		this.model.update(s)
	};
	m.prototype.updateSelectedIndex = function(s) {
		this.model.setSelectedIndex(s.target.selectedIndex)
	};
	m.prototype.selectByText = function(t) {
		var s = this;
		n(this.model.read(), function(v, u) {
			if (v.text.match("^" + t) == t) {
				k("auto detected ", v, u);
				s.model.setSelectedIndex(u);
				s.view.element.selectedIndex = u
			}
		})
	};
	m.prototype.selectByID = function(s) {
		var t = this;
		n(this.model.read(), function(v, u) {
			if (v.id.toString().match("^" + s) == s) {
				k("auto detected ", v, u);
				t.model.setSelectedIndex(u);
				t.view.element.selectedIndex = u
			}
		})
	};
	m.prototype.getValue = function() {
		return this.model.read(this.model.getSelectedIndex()).text
	};
	p = function(s) {
		this.labels = s.labels;
		this.currentGeo = {};
		this.lists = [];
		this.elements = s.elements;
		this.parent = s.parent
	};
	p.prototype.init = function() {
		k("init select group");
		var s = 0, u = this.labels.length, t = this;
		for (; s < u; s++) {
			this.lists.push(new m({
				label : this.labels[s],
				element : this.elements[s],
				level : s,
				parent : t
			}))
		}
		k("lists built ", this);
		k(this.parent.listHelper.find(-1));
		this.lists[0].refresh(this.parent.listHelper.find(-1));
		this.lists[0].view.show()
	};
	p.prototype.update = function(u) {
		if (u.level == this.lists.length - 1) {
			return
		}
		k("Updating SelectGroup contents", this);
		if (u.present === 0) {
			var s = u.level + 1, t = this.lists.length;
			for (; s < t; s++) {
				this.lists[s].refresh();
				this.lists[s].view.hide()
			}
			return
		}
		switch (u.level) {
		case 0:
			this.currentGeo.province = u.presentItem.text;
			break;
		case 1:
			this.currentGeo.city = u.presentItem.text;
			break;
		case 2:
			this.currentGeo.district = u.presentItem.text;
			break
		}
		this.lists[u.level + 1].refresh(this.parent.listHelper.find(u.level,
				u.presentItem.id));
		this.lists[u.level + 1].view.show()
	};
	p.prototype.setValues = function(s) {
		k("setting group values", s);
		var t = this;
		n(s, function(v, u) {
			if (v) {
				t.lists[u].selectByText(v)
			}
		})
	};
	p.prototype.setValuesID = function(s) {
		k("setting group values", s);
		var t = this;
		n(s, function(v, u) {
			if (v) {
				t.lists[u].selectByID(v)
			}
		})
	};
	p.prototype.setValuesCode = function(s) {
		k("setting group values", s);
		var t = this;
		n(s, function(v, u) {
			if (v) {
				t.lists[u].selectByText(v)
			}
		})
	};
	p.prototype.getValues = function() {
		var s = [];
		n(this.lists, function(u, t) {
			s.push(u.getValue())
		});
		return s
	};
	h = function(s) {
		this.detectGeoLocation = s.detectGeoLocation === undefined ? true
				: s.detectGeoLocation;
		this.detector = s.detector || f;
		this.listHelper = s.listHelper || a.getInstance({
			dataUrl : s.dataUrl
		});
		this.selectGroup = new p({
			parent : this,
			labels : s.labels,
			elements : s.elements
		});
		var t = this;
		this.listHelper.fetch(function() {
			k("exec fetech callback");
			t.selectGroup.init();
			if (t.detectGeoLocation) {
				t.detector()
			}
		})
	};
	h.prototype.report = function() {
		return this.selectGroup.getValues()
	};
	h.prototype.select = function(s) {
		this.selectGroup.setValues(s)
	};
	h.prototype.selectID = function(s) {
		this.selectGroup.setValuesID(s)
	};
	f = function() {
		k("Detect!!!!");
		var s = this, t;
		o(
				"http://j.maxmind.com/app/geoip.js",
				function() {
					k("Maxmind API Loaded!");
					t = "http://query.yahooapis.com/v1/public/yql?q=select%20*%20from%20json%20where%0A%20%20url%3D%22http%3A%2F%2Fmaps.google.com%2Fmaps%2Fapi%2Fgeocode%2Fjson%3Flatlng%3D"
							+ geoip_latitude()
							+ "%2C"
							+ geoip_longitude()
							+ "%26sensor%3Dfalse%26language%3Dzh-CN%22&format=json&diagnostics=true&callback=locationselectcb";
					r(
							t,
							function(u) {
								k("Geocoder Request Completed through YQL ", u);
								if (u.query.results.json.status === "OK") {
									var w = u.query.results.json.results[0].address_components, v = {};
									k("Geocoder statuts ok", w);
									n(
											w,
											function(z, x) {
												var y = z.types[0];
												if (y === "locality") {
													v.city = z.long_name
												} else {
													if (y === "administrative_area_level_1") {
														v.province = z.long_name
													}
												}
											});
									s.select([ v.province, v.city ])
								}
							})
				})
	};
	a = function() {
		var s, t = function(v) {
			var w = v.dataUrl || "js/areas.js", u = function() {
				var x = {};
				return {
					get : function(y) {
						return x[y]
					},
					set : function(y, z) {
						x[y] = z
					}
				}
			}();
			return {
				fetch : function(y) {
					k("feteching areas data");
					var x = function(z) {
						k("area data : ", z);
						u.set("province", z.province);
						u.set("city", z.city);
						u.set("district", z.district);
						y()
					};
					g({
						url : w,
						callback : x
					})
				},
				find : function(E, D) {
					var y = [];
					k("querying by record id : ", D, "by list in level : ", E);
					if (u.get(D)) {
						k("lucky! we have it cached");
						y = u.get(D)
					} else {
						k("finding it in areas data");
						if (E === -1) {
							k("this is a query for province data");
							y = u.get("province")
						} else {
							var C = D.toString().substring(0, (E + 1) * 2), A = new RegExp(
									"^" + C + "\\d*"), z = E === 0 ? u
									.get("city") : u.get("district"), x = 0, B = z.length;
							n(z, function(G, F) {
								if (A.test(G.id)) {
									y.push(z[F])
								}
							})
						}
					}
					k("Return results : ", y);
					return y
				}
			}
		};
		return {
			getInstance : function(u) {
				if (!s) {
					s = t(u)
				}
				return s
			}
		}
	}();
	com.elfvision.kit.LocationSelect = h;
	com.elfvision.ajax.XhrFactory = c;
	com.elfvision.ajax.getJson = g;
	com.elfvision.ajax.jsonp = r;
	com.elfvision.ajax.getScript = o
})();

if (window.jQuery !== undefined) {
	$.LocationSelect = {
		build : function(c) {
			var b = c, a;
			b.elements = this.get();
			a = new com.elfvision.kit.LocationSelect(c);
			$.LocationSelect.all[b.name] = a;
			return this
		}
	};
	$.LocationSelect.all = {};
	$.fn.LocationSelect = $.LocationSelect.build
};