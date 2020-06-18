<?php
    header("content-type: text/html; charset=UTF-8");
?>
<?php include(CONST_BasePath.'/lib/template/includes/html-header.php'); ?>
    <link href="css/common.css" rel="stylesheet" type="text/css" />
    <link href="css/search.css" rel="stylesheet" type="text/css" />
</head>

<body id="search-page">

    <?php include(CONST_BasePath.'/lib/template/includes/html-top-navigation.php'); ?>

        <div class="top-bar" id="structured-query-selector">
            <div class="search-type-link">
                <a id="switch-to-reverse" href="<?php echo CONST_Website_BaseURL; ?>reverse.php?format=html">reverse search</a>
            </div>

        <div class="radio-inline">
          <input type="radio" name="query-selector" id="simple" value="simple">
          <label for="simple">simple</label>
        </div>
        <div class="radio-inline">
          <input type="radio" name="query-selector" id="structured" value="structured">
          <label for="structured">structured</label>
        </div>

    <form role="search" accept-charset="UTF-8" action="<?php echo CONST_Website_BaseURL; ?>search.php">
        <div class="form-group-simple">
            <!-- <input id="q" name="q" type="text" class="form-control input-sm" placeholder="Search" value="<?php echo htmlspecialchars($aMoreParams['q'] ?? ''); ?>" > -->
            <input id="q" name="q" type="text" oninput="suggester()" class="form-control input-sm" placeholder="Search" autocomplete="off" list="suglist" value="<?php echo htmlspecialchars($sQuery); ?>" >
            <datalist id="suglist">
                <!-- <option value="Monaco">
                <option value="monaco"> -->
                <!-- <option value="monday"> -->
            </datalist>
        </div>
        <div class="form-group-structured">
<div class="form-inline">
            <input id="street" name="street" type="text" class="form-control input-sm" placeholder="House number/Street" value="<?php echo htmlspecialchars($aMoreParams['street'] ?? ''); ?>" >
            <input id="city" name="city" type="text" class="form-control input-sm" placeholder="City" value="<?php echo htmlspecialchars($aMoreParams['city'] ?? ''); ?>" >
            <input id="county" name="county" type="text" class="form-control input-sm" placeholder="County" value="<?php echo htmlspecialchars($aMoreParams['county'] ?? ''); ?>" >
            <input id="state" name="state" type="text" class="form-control input-sm" placeholder="State" value="<?php echo htmlspecialchars($aMoreParams['state'] ?? ''); ?>" >
            <input id="country" name="country" type="text" class="form-control input-sm" placeholder="Country" value="<?php echo htmlspecialchars($aMoreParams['country'] ?? ''); ?>" >
            <input id="postalcode" name="postalcode" type="text" class="form-control input-sm" placeholder="Postal Code" value="<?php echo htmlspecialchars($aMoreParams['postalcode'] ?? ''); ?>" >
        </div></div>
        <div class="form-group search-button-group">
            <button type="submit" class="btn btn-primary btn-sm">Search</button>
            <?php if (CONST_Search_AreaPolygons) { ?>
                <input type="hidden" value="1" name="polygon_geojson" />
            <?php } ?>
            <input type="hidden" name="viewbox" value="<?php echo htmlspecialchars($aMoreParams['viewbox'] ?? ''); ?>" />
            <div class="checkbox-inline">
                <input type="checkbox" id="use_viewbox" <?php if (!empty($aMoreParams['viewbox'])) echo "checked='checked'"; ?>>
                <label for="use_viewbox">apply viewbox</label>
            </div>
        </div>
    </form>
</div>

    <div id="content">

<?php if ($sQuery) { ?>

        <div id="searchresults" class="sidebar">
        <?php
            $i = 0;
            foreach($aSearchResults as $iResNum => $aResult)
            {

                echo '<div class="result" data-position=' . $i . '>';

                echo (isset($aResult['icon'])?'<img alt="icon" src="'.$aResult['icon'].'"/>':'');
                echo ' <span class="name">'.htmlspecialchars($aResult['name']).'</span>';
                // echo ' <span class="latlon">'.round($aResult['lat'],3).','.round($aResult['lon'],3).'</span>';
                // echo ' <span class="place_id">'.$aResult['place_id'].'</span>';
                if (isset($aResult['label']))
                    echo ' <span class="type">('.$aResult['label'].')</span>';
                else if ($aResult['type'] == 'yes')
                    echo ' <span class="type">('.ucwords(str_replace('_',' ',$aResult['class'])).')</span>';
                else
                    echo ' <span class="type">('.ucwords(str_replace('_',' ',$aResult['type'])).')</span>';
                echo detailsPermaLink($aResult, 'details', 'class="btn btn-default btn-xs details"');
                echo '</div>';
                $i = $i+1;
            }
            if (!empty($aSearchResults) && $sMoreURL)
            {
                echo '<div class="more"><a class="btn btn-primary" href="'.htmlentities($sMoreURL).'">Search for more results</a></div>';
            }
            else
            {
                echo '<div class="noresults">No search results found</div>';
            }

        ?>
        </div>

<?php } else { ?>

        <div id="intro" class="sidebar">
            <?php include(CONST_BasePath.'/lib/template/includes/introduction.php'); ?>
        </div>

<?php } ?>

        <div id="map-wrapper">
            <div id="map-position">
                <div id="map-position-inner"></div>
                <div id="map-position-close"><a href="#">hide</a></div>
            </div>
            <div id="map"></div>
        </div>

    </div> <!-- /content -->



    <script type="text/javascript">
    function suggester()
    {
        var sp = document.getElementById("sp");
        var query = document.getElementById("q").value;
        // console.log(window.location.href + "?q=" + query + "&polygon_geojson=1");
        // $.ajax({
        //     type: "POST",
        //     url: "../lib/es.py",
        //     data: { param: query },
        //     success: callbackFunc,
        //     error: callbackFunc
        // });

        var xmlhttp = new XMLHttpRequest();
        var url = "http://localhost:8000/pref/?q=" + query;
        console.log(url);

        xmlhttp.onreadystatechange = function() {
            // if(!(this.responseText === ""))
            // {
            //     console.log(this.status, this.responseText);
            // }
            if (this.readyState == 4 && this.status == 200) {
                var myArr = JSON.parse(this.responseText);
                // var hits = myArr['features'];
                var hits = myArr['hits'].hits;
                // console.log(hits, hits.length);
                var options = '';
                for(var i = 0; i < hits.length; i++)
                {
                    res = '';
                    // console.log(hits[i]['properties'].length);
                    // for(var j in hits[i]['properties'])
                    // {
                    //     res += hits[i]['properties'][j];
                    //     if(j != 0 && hits[i]['properties'][j] !== '')
                    //         res += ", ";
                    // }

                    // console.log(i, hits[i]._source.name);
                    res = hits[i]._source.name;
                    // if(hits[i]._source.hasOwnProperty('name'))
                    // if(hits[i]._source.hasOwnProperty('city'))
                    //     res +=  ", " + hits[i]._source.city['default'];
                    // if(hits[i]._source.hasOwnProperty('country'))
                    //     res += ", " + hits[i]._source['country']['default'];
                    // if(hits[i]._source.hasOwnProperty('osm_value'))
                    //     res += " (" + hits[i]._source['osm_value'] + ")";
                    // if(res === '')
                    //     res = query;
                    res = hits[i]._source.name;
                    res = res.replace(/\"/g, "'")
                    // console.log(res);
                    // Using photon API
                    // if(hits[i]['properties'].hasOwnProperty('name'))
                    //     res += hits[i]['properties']['name'];
                    // if(hits[i]['properties'].hasOwnProperty('city'))
                    //     res +=  ", " + hits[i]['properties']['city'];
                    // if(hits[i]['properties'].hasOwnProperty('country'))
                    //     res += ", " + hits[i]['properties']['country'];
                    // if(hits[i]['properties'].hasOwnProperty('osm_value'))
                    //     res += " (" + hits[i]['properties']['osm_value'] + ")";
                    // if(res === '')
                    //     res = query;
                    options += '<option value="' + res + '" />';
                    // console.log( hits[i]['properties']['osm_id'] );
                    
                }
                document.getElementById('suglist').innerHTML = options;
                console.log(document.getElementById('suglist').innerHTML);
            }
        };


        xmlhttp.open("GET", url, true);
        xmlhttp.send();

        function myFunction(arr) {
            var out = "";
            var i;
            for(i = 0; i < arr.length; i++) {
                out += '<a href="' + arr[i].url + '">' +
                arr[i].display + '</a><br>';
            }
            document.getElementById("content").innerHTML = out;
        }

        // xhr = createCORSRequest('GET', 'http://127.0.0.1:5001/square/');
        // console.log(xhr.send());
        // console.log(createCORSRequest('POST', 'http://127.0.0.1:5001/square/').send());
        // $.ajax({
        //     url: 'http://127.0.0.1:5001/generate/',
        //     data: {'number': query},
        //     method: 'POST',
        //     success: callbackFunc
        // });
        // $.getJSON('http://localhost:9200/bank/_search', 
        //     function(data, textStatus, jqXHR) {
        //         alert(data);
        //     }
        // );
    }

    function createCORSRequest(method, url){
        var xhr = new XMLHttpRequest();
        if ("withCredentials" in xhr){
            // XHR has 'withCredentials' property only if it supports CORS
            xhr.open(method, url, true);
        } else if (typeof XDomainRequest != "undefined"){ // if IE use XDR
            xhr = new XDomainRequest();
            xhr.open(method, url);
        } else {
            xhr = null;
        }
        return xhr;
    }

    function callbackFunc(response) {
        // do something with the response
        console.log(response);
    }

    <?php

        $aNominatimMapInit = array(
            'zoom' => CONST_Default_Zoom,
            'lat' => CONST_Default_Lat,
            'lon' => CONST_Default_Lon,
            'tile_url' => CONST_Map_Tile_URL,
            'tile_attribution' => CONST_Map_Tile_Attribution
        );
        echo 'var nominatim_map_init = ' . json_encode($aNominatimMapInit, JSON_PRETTY_PRINT) . ';';

        echo 'var nominatim_results = ' . json_encode($aSearchResults, JSON_PRETTY_PRINT) . ';';
        $sStructuredQuery = (empty($aMoreParams['q'])
                             && !(empty($aMoreParams['street'])
                                  && empty($aMoreParams['city'])
                                  && empty($aMoreParams['county'])
                                  && empty($aMoreParams['state'])
                                  && empty($aMoreParams['country'])
                                  && empty($aMoreParams['postalcode'])))
                            ? 'true' : 'false';
        echo 'var nominatim_structured_query = '.$sStructuredQuery.';';
    ?>
    </script>
    <?php include(CONST_BasePath.'/lib/template/includes/html-footer.php'); ?>

</body>
</html>
