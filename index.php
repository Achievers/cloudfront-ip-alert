<?php
/**
 * Documentaiton
 * http://docs.aws.amazon.com/general/latest/gr/aws-ip-ranges.html
 */
require_once('src/IPWhiteList.class.php');

use Achievers\CloudFront\IPWhiteList;

$cloudFrontIP = new IPWhiteList();
$listOfIPs = $cloudFrontIP->getLastCloudFrontFile();
$data = $cloudFrontIP->checkIP($listOfIPs);
?>
<html>
<head>
    <script type="text/javascript" src="js/jquery.min.js"></script>
    <script type="text/javascript" src="js/underscore.js"></script>
    <script type="text/javascript" src="js/backbone.js"></script>
    <script type="text/template" id="ip_list_item_template">
        <li class="{{data.className}}">{{data.ip}}</li>
    </script>

    <style>
        .new {
            color: green;
            font-weight: bold;
        }
        .deleted {
            color: red;
            text-decoration: line-through;
        }
    </style>
</head>
<body>
    <h1 class="title">CloudFront IP changes</h1>
    <div class="container"></div>

    <script type="text/javascript">
        //Data
        var cloudFrontData = <?php echo $data; ?>;

        //Model
        var CloudFrontModel = Backbone.Model.extend({});
        var IPAddressModel = Backbone.Model.extend({});
        var cloudFrontObj = new CloudFrontModel(cloudFrontData);

        //Collection
        var IPAddressCollection = Backbone.Collection.extend({
            model: IPAddressModel
        });

        //View
        var IPAddressView = Backbone.View.extend({
            tagName: "li",
            initialize: function(){
                //this.render();
            },
            render: function(){
                var data = {};
                data.ip = this.model.get('ip');
                data.className = this.model.get('className');

                // Compile the template using underscore
                _.templateSettings = {
                    interpolate: /\{\{(.+?)\}\}/g
                };
                var template = _.template( $("#ip_list_item_template").html(), {variable: 'data'});
                template(data);
                // Load the compiled HTML into the Backbone "el"
                this.$el.html(template(data));
                return this;
            }
        });

        var IPAddressListView = Backbone.View.extend({
            el: $('.container'),
            initialize: function() {
                this.$el.append($('<h3>'));
                this.$el.append($('<ul>'));
                this.ipMessage = this.$el.find('h3');
                this.ipAddressList = this.$el.find('ul');
                this.render();
            },
            render: function() {
                var that = this;
                var lastListOfIPs = this.model.get('lastListOfIPs');
                var currentListOfIPs = this.model.get('currentListOfIPs');
                var inOldButNotInNew = _.difference(lastListOfIPs, currentListOfIPs);
                var inNewButNotInOld = _.difference(currentListOfIPs, lastListOfIPs);
                var ipAddresses = [];

                this.ipMessage.html(this.model.get('message'));


                $(_.union(currentListOfIPs, inOldButNotInNew)).each(function (index, ipAddress) {
                    var className = '';
                    if (inNewButNotInOld.indexOf(ipAddress) > -1) {
                        //exist in current one but missing in the old IPs, this is a new IP.
                        className = 'new';
                    }
                    if (inOldButNotInNew.indexOf(ipAddress) > -1) {
                        className = 'deleted';
                    }
                    //Create a single IP Address model based on the given data
                    ipAddressModel = new IPAddressModel({
                        ip: ipAddress,
                        className: className
                    });
                    ipAddresses.push(ipAddressModel);
                });

                //Display collection
                var ipAddressToDisplay = new IPAddressCollection(ipAddresses);
                ipAddressToDisplay.each(function(element, index) {
                    var singleIPAddressView = new IPAddressView({
                        model: element
                    });
                    that.ipAddressList.append(singleIPAddressView.render().el);
                })
            }
        });

        //Start App
        var ipAddressListView = new IPAddressListView({
            model: cloudFrontObj
        });
    </script>
</body>
</html>