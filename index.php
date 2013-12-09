<?php
require_once('config.php');
$city = array('1' => 'Delhi', '2' => 'Chandigarh');
$state = array('1' => 'Delhi', '2' => 'Punjab');

$displayMap = FALSE;
if (count($_POST) > 0) {
    if (isset($_POST['srch2'])) {
        $contents = file_get_contents("http://maps.googleapis.com/maps/api/geocode/json?address=" . urlencode($_POST['address']) . "&sensor=true");
    }
    if (isset($_POST['srch1'])) {
        $contents = file_get_contents("http://maps.googleapis.com/maps/api/geocode/json?address=" . urlencode($city[$_POST['city']]) . "," . urlencode($state[$_POST['state']]) . "&sensor=true");
    }
    $details = json_decode($contents, TRUE);
    if ($details['status'] == 'OK') {
        $lat = $details['results'][0]['geometry']['location']['lat'];
        $long = $details['results'][0]['geometry']['location']['lng'];
        $makeCenter = false;
        $markers = "";
        if (isset($_POST['srch2'])) {
            $center = $lat . "," . $long;
            $longRange = ($long - .01) . " and " . ($long + .01);
            $latRange = ($lat - .01) . " and " . ($lat + .01);
            
            $sql = "SELECT * FROM  atm_address where lat between $latRange and lng between $longRange";
        }
        if (isset($_POST['srch1'])) {
            $sql = "SELECT * FROM  atm_address where city_id=" . $_POST['city'] . " and state_id=" . $_POST['state'];
            $makeCenter = true;
        }


        $rs = mysql_query($sql);
        if (mysql_num_rows($rs) > 0)
            $displayMap = true;
        while ($row = mysql_fetch_assoc($rs)) {
            if ($makeCenter == true) {
                $center = $row['lat'] . "," . $row['lng'];
                $makeCenter = false;
            }
            $markers.='{"title":"' . stripcslashes($row['atm_name']) . '","lat":"' . $row['lat'] . '","lng":"' . $row['lng'] . '","description":"' . stripcslashes($row['address']) . '"},';
        }
        $markers = substr($markers, 0, -1);
    }
}
?>
<!DOCTYPE html>
<html>
    <head>
        <meta name="viewport" content="initial-scale=1.0, user-scalable=no">
        <meta charset="utf-8">	
        <script type="text/javascript" src = "https://maps.googleapis.com/maps/api/js?key=<?php echo GOOGLE_API_KEY;?>&sensor=false">
        </script> 
        <?php if ($displayMap) { ?>
            <script type="text/javascript">
                function initialize() {
                    var markers = JSON.parse('[<?php echo $markers; ?>]');
                    var mapOptions = {
                        center: new google.maps.LatLng(<?php echo $center; ?>),
                        zoom: 14,
                        mapTypeId: google.maps.MapTypeId.ROADMAP,
                        marker:true
                    };
                    var infoWindow = new google.maps.InfoWindow();
                    var map = new google.maps.Map(document.getElementById("map_canvas"), mapOptions);
                    for (i = 0; i < markers.length; i++) {
                        var data = markers[i]
                        var myLatlng = new google.maps.LatLng(data.lat, data.lng);
                        var marker = new google.maps.Marker({
                            position: myLatlng,
                            map: map,
                            title: data.description,
                            icon:'logo_map.png'
                        });
                        (function (marker, data) {

                            // Attaching a click event to the current marker
                            google.maps.event.addListener(marker, "click", function (e) {
                                infoWindow.setContent(data.description);
                                infoWindow.open(map, marker);
                            });
                        })(marker, data);
                    }
                }
            </script>

        <?php } ?>
    </head>
    <body onload="<?php if ($displayMap) echo 'initialize()'; ?>">
		<h2>Search ATM by State & City</h2>
        <form action="" method="post" name="gmapselect" id="gmapselect">
            <select name="state" id="state">
                <option value="1">Delhi</option>
                <option value="2">Punjab</option>
            </select>
            <select name="city" id="city">
                <option value="1">Delhi</option>
                <option value="2">Chandigarh</option>
                <input type="submit" name="srch1" id="srch1" value="Search"/>
        </form>
        <br/>
        <hr/>
        <br/>
        <h2>Search by address</h2>
        <form action="" method="post" name="gmapsrch" id="gmapsrch">
            <input type="text" name="address" id="address" placeholder="type address" />
            <input type="submit" name="srch2" id="srch2" value="Search"/>
        </form>
        <?php if ($displayMap == FALSE) echo'No ATMS found'; ?>
        <div id="map_canvas" style="width: 850px; height: 550px"></div>
    </body>
</html>
