//
// The app to manage web options for a business
//
function ciniki_web_info() {
	
	this.activeToggles = {'no':'No', 'yes':'Yes'};
	this.subpages = {
		'2':{'name':'Artist Statement', 'permalink':'artiststatement', 'flags':0x02},
		'3':{'name':'CV', 'permalink':'cv', 'flags':0x04},
		'4':{'name':'Awards', 'permalink':'awards', 'flags':0x08},
		'5':{'name':'History', 'permalink':'history', 'flags':0x10},
		'6':{'name':'Donations', 'permalink':'donations', 'flags':0x20},
		'9':{'name':'Facilities', 'permalink':'facilities', 'flags':0x100},
		'8':{'name':'Board of Directors', 'permalink':'boardofdirectors', 'flags':0x80},
		'7':{'name':'Membership', 'permalink':'membership', 'flags':0x40},
		'11':{'name':'Warranty', 'permalink':'warranty', 'flags':0x0400},
		'12':{'name':'Testimonials', 'permalink':'testimonials', 'flags':0x0800},
		'13':{'name':'Reviews', 'permalink':'reviews', 'flags':0x1000},
		'14':{'name':'Green Policy', 'permalink':'greenpolicy', 'flags':0x2000},
		'15':{'name':'Why us', 'permalink':'whyus', 'flags':0x4000},
		'16':{'name':'Privacy Policy', 'permalink':'privacypolicy', 'flags':0x8000},
		'17':{'name':'Volunteer', 'permalink':'volunteer', 'flags':0x010000},
		'18':{'name':'Rental', 'permalink':'rental', 'flags':0x020000},
		'19':{'name':'Financial Assistance', 'permalink':'financialassistance', 'flags':0x040000},
		'20':{'name':'Artists', 'permalink':'artists', 'flags':0x080000},
		'21':{'name':'Employment', 'permalink':'employment', 'flags':0x100000},
		'22':{'name':'Staff', 'permalink':'Staff', 'flags':0x200000},
	};
	
	this.init = function() {
		//
		// The options and information for the info page
		//
		this.page = new M.panel('Information',
			'ciniki_web_info', 'page',
			'mc', 'medium', 'sectioned', 'ciniki.web.info.page');
		this.page.data = {};
		this.page.sections = {
			'options':{'label':'', 'fields':{
				'page-info-active':{'label':'Display Info Page', 'type':'multitoggle', 'default':'no', 'toggles':this.activeToggles},
				'page-info-title':{'label':'Title', 'type':'text', 'hint':'Info'},
				'page-info-defaultcontenttype':{'label':'Start Page', 'type':'select', 'options':{}},
				}},
			'subpages':{'label':'', 'fields':{}},
			'_save':{'label':'', 'buttons':{
				'save':{'label':'Save', 'fn':'M.ciniki_web_info.savePage();'},
				}},
		};
		this.page.fieldValue = function(s, i, d) { 
			if( this.data[i] == null ) { return ''; }
			return this.data[i]; 
		};
		this.page.fieldHistoryArgs = function(s, i) {
			return {'method':'ciniki.web.pageSettingsHistory', 'args':{'business_id':M.curBusinessID, 'field':i}};
		}
		this.page.addButton('save', 'Save', 'M.ciniki_web_info.savePage();');
		this.page.addClose('Cancel');
	}

	this.start = function(cb, ap, aG) {
		args = {};
		if( aG != null ) { args = eval(aG); }

		//
		// Create the app container if it doesn't exist, and clear it out
		// if it does exist.
		//
		var appContainer = M.createContainer(ap, 'ciniki_web_info', 'yes');
		if( appContainer == null ) {
			alert('App Error');
			return false;
		} 

		this.showPage(cb);
	}

	this.showPage = function(cb) {
		this.page.reset();
		M.api.getJSONCb('ciniki.web.pageSettingsGet', {'business_id':M.curBusinessID, 
			'page':'info', 'content':'yes'}, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				var flags = M.curBusiness.modules['ciniki.info'].flags;
				var p = M.ciniki_web_info.page;
				p.data = rsp.settings;
				p.sections.subpages.fields = {};
				var options = {};
				var spgs = M.ciniki_web_info.subpages;
				for(i in spgs) {
					if( (spgs[i].flags&flags) > 0 ) {	
						options[i] = spgs[i].name;
						p.sections.subpages.fields['page-info-' + spgs[i].permalink + '-active'] = {'label':'Display ' + spgs[i].name,
							'type':'toggle', 'default':'no', 'toggles':M.ciniki_web_info.activeToggles};
					}
				}
				p.sections.options.fields['page-info-defaultcontenttype'].options = options;
				p.refresh();
				p.show(cb);
			});
	}

	this.savePage = function() {
		var c = this.page.serializeForm('no');
		if( c != '' ) {
			M.api.postJSONCb('ciniki.web.siteSettingsUpdate', 
				{'business_id':M.curBusinessID}, c, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					} 
					M.ciniki_web_info.page.close();
				});
		} else {
			this.page.close();
		}
	};
}
