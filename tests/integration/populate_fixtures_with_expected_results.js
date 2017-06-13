var Vue = require( 'vue' );
const cheerio = require( 'cheerio' );
const renderer = require( 'vue-server-renderer' ).createRenderer();
const fs = require( 'fs' );

const fixtureDir = __dirname + '/fixture';

readFixtureDir().then( function ( files ) {
	files.forEach( function ( fileName ) {
		var filePath = fixtureDir + '/' + fileName;
		readFile( filePath )
			.then( extractDataFromFixture )
			.then( renderTemplate )
			.then( removeServerRenderedDataAttribute )
			.then( saveResultToFile.bind(undefined, filePath) )
			.then( function ( ) {
				console.log( 'saved ' + fileName );
			} )
	} );
} );


function extractDataFromFixture( html ) {
	const $ = cheerio.load( html );

	const template = $( '#template' ).html().trim().replace(/\&apos;/g, "'"); //Replacing '&apos;' as soon as Vue can't handle it
	console.log( template );
	const data = JSON.parse( $( '#data' ).html() );

	return { template: template, data: data };
}

function renderTemplate( fixtureData ) {
	return new Promise( function ( resolve, reject ) {
		const app = new Vue( {
			template: fixtureData.template,
			data: fixtureData.data
		} );

		renderer.renderToString( app, function ( err, html ) {
			if ( err ) {
				reject( err )
			} else {
				resolve( html );
			}
		} );
	} );
}

function removeServerRenderedDataAttribute( html ) {
	const $ = cheerio.load( html );

	$.root().children().each(function() {
		 $(this).removeAttr('data-server-rendered');
	});

	return $.html();
}

function readFile( filePath ) {
	return new Promise( function ( resolve, reject ) {
		fs.readFile( filePath, 'utf8', function ( err, data ) {
			if ( err ) {
				reject( err )
			} else {
				resolve( data );
			}
		} );
	} );
}
function saveFile( filePath, contents ) {
	return new Promise( function ( resolve, reject ) {
		fs.writeFile( filePath, contents, 'utf8', function ( err ) {
			if ( err ) {
				reject( err )
			} else {
				resolve();
			}
		} );
	} );
}



function readFixtureDir() {
	return new Promise( function ( resolve, reject ) {
		fs.readdir( fixtureDir, function ( err, items ) {
			if ( err ) {
				reject( err )
			} else {
				resolve( items );
			}
		} );
	} );
}

function saveResultToFile(filePath, renderResult) {
	readFile(filePath).then(function (html) {
		const $ = cheerio.load( html );
		$('#result').remove();
		const $resultElement = $( '<div id="result"></div>' ).html( "\n\t" + renderResult + "\n" );
		$.root().children().last().after( $resultElement ).after("\n");

		return saveFile( filePath, $.html().trim() + "\n" );
	})

}
