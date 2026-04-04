<?php
// download_template.php
header ( 'Content-Type: application/vnd.ms-excel' );
header ( 'Content-Disposition: attachment; filename="Shipment_Bulk_Template.xls"' );

$dd   = date ( 'd' );
$mm   = date ( 'm' );
$yyyy = date ( 'Y' );
?>
<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel"
    xmlns="http://www.w3.org/TR/REC-html40">

<head>
    <style>
        .required {
            background-color: #ffcccc;
            font-weight: bold;
        }

        .optional {
            background-color: #e0e0e0;
            font-weight: bold;
        }

        td {
            mso-number-format: "\@";
        }

        .example-header {
            background-color: #f8f9fa;
            font-weight: bold;
            text-align: center;
        }
    </style>
</head>

<body>
    <table border="1">
        <tr>
            <!-- 0..4: Origin Info -->
            <th class="required">Branch Name</th>
            <th class="required">Booking Type</th>
            <th class="required">Date (DD)</th>
            <th class="required">Month (MM)</th>
            <th class="required">Year (YYYY)</th>
            <th class="optional">Ref ID</th>
            <th class="required">Courier</th>

            <!-- 5..10: Consignor (Manual Flow order: Name, Phone, PIN, Addr, City, State) -->
            <th class="required">Shipper Name</th>
            <th class="required">Shipper Phone</th>
            <th class="required">Shipper Pin</th>
            <th class="required">Shipper Address</th>
            <th class="required">Shipper City</th>
            <th class="required">Shipper State</th>

            <!-- 11..18: Consignee (Manual Flow: Name, Phone, Email, GST, Addr, PIN, City, State) -->
            <th class="required">Consignee Name</th>
            <th class="required">Consignee Phone</th>
            <th class="optional">Consignee Email (Required for Shiprocket)</th>
            <th class="optional">Consignee GST</th>
            <th class="required">Consignee Address</th>
            <th class="required">Consignee Pin</th>
            <th class="required">Consignee City</th>
            <th class="required">Consignee State</th>

            <!-- 19..21: Package Stats -->
            <th class="required">Payment Mode (Prepaid/COD)</th>
            <th class="optional">COD Amount</th>
            <th class="optional">Product Desc</th>

            <!-- 22..26: Dimensions -->
            <th class="required">Length (cm)</th>
            <th class="required">Width (cm)</th>
            <th class="required">Height (cm)</th>
            <th class="required">Weight (kg/box)</th>
            <th class="required">Boxes</th>

            <!-- 27..30: Extra -->
            <th class="optional">Invoice No</th>
            <th class="optional">Invoice Value</th>
            <th class="optional">Ewaybill No (Required if Value > 50k)</th>
            <th class="required">Shipping Mode (Surface/Air/Express)</th>
            <th class="optional">Client Name</th>
            <th class="optional">AWB No (Own Courier: leave empty to assign from allocation)</th>
            <th class="optional">Pickup Point Name (Required for Delhivery/Shiprocket)</th>
            <th class="optional">Shiprocket Courier Company ID (Optional)</th>
        </tr>

        <!-- Row 1: Single box, Prepaid, Express — Own Courier -->
        <tr>
            <td>Bangalore</td><td>Forward</td>
            <td><?php echo $dd; ?></td><td><?php echo $mm; ?></td><td><?php echo $yyyy; ?></td>
            <td>PRE-EXP-001</td><td>Sri Hari</td>
            <td>ABC Traders</td><td>9876543210</td><td>400001</td><td>123 Merchant Lane</td><td>Mumbai</td><td>Maharashtra</td>
            <td>John Doe</td><td>9123456780</td><td>john@example.com</td><td></td>
            <td>456 Park Avenue</td><td>110001</td><td>Delhi</td><td>Delhi</td>
            <td>Prepaid</td><td>0</td><td>Electronics</td>
            <td>15</td><td>15</td><td>15</td><td>2.5</td><td>1</td>
            <td>INV-101</td><td>1200</td><td></td><td>Express</td><td>ABC Client</td><td></td><td></td><td></td>
        </tr>

        <!-- Row 2: Single box, COD, Surface — Own Courier -->
        <tr>
            <td>Bangalore</td><td>Forward</td>
            <td><?php echo $dd; ?></td><td><?php echo $mm; ?></td><td><?php echo $yyyy; ?></td>
            <td>COD-SUR-002</td><td>Sri Hari</td>
            <td>ABC Traders</td><td>9876543210</td><td>400001</td><td>123 Merchant Lane</td><td>Mumbai</td><td>Maharashtra</td>
            <td>Jane Smith</td><td>9888777666</td><td>jane@example.com</td><td>27AADCB1234F1Z1</td>
            <td>789 Industrial Area</td><td>560001</td><td>Bangalore</td><td>Karnataka</td>
            <td>COD</td><td>2500</td><td>Hardware</td>
            <td>30</td><td>30</td><td>20</td><td>5.0</td><td>1</td>
            <td>INV-202</td><td>2500</td><td></td><td>Surface</td><td>XYZ Corp</td><td></td><td></td><td></td>
        </tr>

        <!--
            Row 3: Delhivery B2C — specify Pickup Point Name (col 36).
            Pickup request is auto-raised after booking.
        -->
        <tr style="background:#e8f4fd;">
            <td>Bangalore</td><td>Forward</td>
            <td><?php echo $dd; ?></td><td><?php echo $mm; ?></td><td><?php echo $yyyy; ?></td>
            <td>DEL-001</td><td>Delhivery</td>
            <td>ABC Traders</td><td>9876543210</td><td>400001</td><td>123 Merchant Lane</td><td>Mumbai</td><td>Maharashtra</td>
            <td>Amit Shah</td><td>9700011111</td><td>amit@example.com</td><td></td>
            <td>12 Green Park</td><td>302001</td><td>Jaipur</td><td>Rajasthan</td>
            <td>Prepaid</td><td>0</td><td>Apparel</td>
            <td>20</td><td>15</td><td>10</td><td>1.5</td><td>1</td>
            <td>INV-501</td><td>1500</td><td></td><td>Surface</td><td>ABC Client</td><td></td><td>Bangalore Warehouse</td><td></td>
        </tr>

        <!--
            Row 4: Shiprocket — include consignee email and pickup point name.
            This helps Shiprocket order creation and AWB assignment.
        -->
        <tr style="background:#eefaf1;">
            <td>Bangalore</td><td>Forward</td>
            <td><?php echo $dd; ?></td><td><?php echo $mm; ?></td><td><?php echo $yyyy; ?></td>
            <td>SR-001</td><td>Shiprocket</td>
            <td>ABC Traders</td><td>9876543210</td><td>400001</td><td>123 Merchant Lane</td><td>Mumbai</td><td>Maharashtra</td>
            <td>Priya Nair</td><td>9988776655</td><td>priya.nair@example.com</td><td></td>
            <td>14 Lake View Road</td><td>560034</td><td>Bangalore</td><td>Karnataka</td>
            <td>Prepaid</td><td>0</td><td>Fashion Item</td>
            <td>22</td><td>18</td><td>12</td><td>1.8</td><td>1</td>
            <td>INV-SR-001</td><td>1800</td><td></td><td>Surface</td><td>ABC Client</td><td></td><td>Bangalore Warehouse</td><td>142</td>
        </tr>

        <!--
            Row 5: Boxes = 2  (SAME DIMENSION SUB-BOXES) — Own Courier
            System auto-creates 2 rows in tbl_booking_packages with same L/W/H:
              Box 1  → AWB = parent serial (e.g. SUR-010)
              Box 2  → AWB = SUR-010-1  (auto-derived, no extra serial used)
        -->
        <tr style="background:#fffbe6;">
            <td>Bangalore</td><td>Forward</td>
            <td><?php echo $dd; ?></td><td><?php echo $mm; ?></td><td><?php echo $yyyy; ?></td>
            <td>MULTI-BOX-003</td><td>Sri Hari</td>
            <td>ABC Traders</td><td>9876543210</td><td>400001</td><td>123 Merchant Lane</td><td>Mumbai</td><td>Maharashtra</td>
            <td>Raj Kumar</td><td>9000022222</td><td>raj@example.com</td><td></td>
            <td>Shop No 5, Market Road</td><td>500001</td><td>Hyderabad</td><td>Telangana</td>
            <td>Prepaid</td><td>0</td><td>Garments</td>
            <td>25</td><td>20</td><td>15</td><td>3.0</td><td>2</td>
            <td>INV-303</td><td>3000</td><td></td><td>Surface</td><td>ABC Client</td><td></td><td></td><td></td>
        </tr>

        <!--
            Row 6+7: MPS — different-sized boxes under same Ref ID.
            Use a separate Excel ROW per package type (boxes=1 each).
            Same Ref ID groups them into one shipment.
        -->
        <tr>
            <td>Bangalore</td><td>Forward</td>
            <td><?php echo $dd; ?></td><td><?php echo $mm; ?></td><td><?php echo $yyyy; ?></td>
            <td>MPS-ORD-004</td><td>Sri Hari</td>
            <td>ABC Traders</td><td>9876543210</td><td>400001</td><td>123 Merchant Lane</td><td>Mumbai</td><td>Maharashtra</td>
            <td>Consolidated Store</td><td>9000011111</td><td>contact@store.com</td><td></td>
            <td>Office Complex A</td><td>700001</td><td>Kolkata</td><td>West Bengal</td>
            <td>Prepaid</td><td>0</td><td>Multi Items</td>
            <td>20</td><td>20</td><td>20</td><td>3.0</td><td>1</td>
            <td>INV-404</td><td>5000</td><td></td><td>Surface</td><td>ABC Client</td><td></td><td></td><td></td>
        </tr>
        <tr>
            <td>Bangalore</td><td>Forward</td>
            <td><?php echo $dd; ?></td><td><?php echo $mm; ?></td><td><?php echo $yyyy; ?></td>
            <td>MPS-ORD-004</td><td>Sri Hari</td>
            <td>ABC Traders</td><td>9876543210</td><td>400001</td><td>123 Merchant Lane</td><td>Mumbai</td><td>Maharashtra</td>
            <td>Consolidated Store</td><td>9000011111</td><td>contact@store.com</td><td></td>
            <td>Office Complex A</td><td>700001</td><td>Kolkata</td><td>West Bengal</td>
            <td>Prepaid</td><td>0</td><td>Multi Items</td>
            <td>40</td><td>40</td><td>10</td><td>6.0</td><td>1</td>
            <td>INV-404</td><td>5000</td><td></td><td>Surface</td><td>ABC Client</td><td></td><td></td><td></td>
        </tr>
    </table>

    <br><br>
    <table border="1">
        <tr>
            <th class="example-header">COURIER ID</th>
            <th class="example-header">COURIER NAME</th>
        </tr>
        <tr><td>1</td><td>Blue Dart</td></tr>
        <tr><td>4</td><td>Amazon Shipping 5Kg</td></tr>
        <tr><td>6</td><td>DTDC Surface</td></tr>
        <tr><td>10</td><td>Delhivery</td></tr>
        <tr><td>14</td><td>Ecom Express Surface</td></tr>
        <tr><td>18</td><td>DTDC 5kg</td></tr>
        <tr><td>19</td><td>Ecom Express Surface 2kg</td></tr>
        <tr><td>23</td><td>Xpressbees 1kg</td></tr>
        <tr><td>24</td><td>Xpressbees 2kg</td></tr>
        <tr><td>25</td><td>Xpressbees 5kg</td></tr>
        <tr><td>29</td><td>Amazon Shipping 1Kg</td></tr>
        <tr><td>32</td><td>Amazon Shipping 2Kg</td></tr>
        <tr><td>33</td><td>Xpressbees</td></tr>
        <tr><td>35</td><td>Aramex International</td></tr>
        <tr><td>39</td><td>Delhivery Surface 5 Kgs</td></tr>
        <tr><td>43</td><td>Delhivery Surface</td></tr>
        <tr><td>45</td><td>Ecom Express Reverse</td></tr>
        <tr><td>46</td><td>Shadowfax Reverse</td></tr>
        <tr><td>48</td><td>Ekart Logistics</td></tr>
        <tr><td>51</td><td>Xpressbees Surface</td></tr>
        <tr><td>54</td><td>Ekart Logistics Surface</td></tr>
        <tr><td>55</td><td>Blue Dart Surface</td></tr>
        <tr><td>58</td><td>Shadowfax Surface</td></tr>
        <tr><td>60</td><td>Ecom Premium and ROS</td></tr>
        <tr><td>61</td><td>Delhivery Reverse</td></tr>
        <tr><td>64</td><td>Delhivery 500G Savex (A)</td></tr>
        <tr><td>65</td><td>Delhivery 500G Savex (S)</td></tr>
        <tr><td>66</td><td>Ecom Exp Savex (A)</td></tr>
        <tr><td>67</td><td>Ecom ROS Savex (A)</td></tr>
        <tr><td>69</td><td>Kerry Indev Express Surface</td></tr>
        <tr><td>71</td><td>Self Delivery</td></tr>
        <tr><td>72</td><td>Bluedart Savex (A)</td></tr>
        <tr><td>75</td><td>SRF Standard 500gm</td></tr>
        <tr><td>76</td><td>Delhivery 5KG Savex (S)</td></tr>
        <tr><td>77</td><td>Delhivery 10KG Savex (S)</td></tr>
        <tr><td>78</td><td>Delhivery 20KG Savex (S)</td></tr>
        <tr><td>79</td><td>SRF Standard 2kg</td></tr>
        <tr><td>81</td><td>SRF Standard</td></tr>
        <tr><td>82</td><td>DTDC 2kg</td></tr>
        <tr><td>83</td><td>Delhivery 500G Savex RVP</td></tr>
        <tr><td>84</td><td>Delhivery 500G Savex RVP-QC</td></tr>
        <tr><td>85</td><td>Standard</td></tr>
        <tr><td>86</td><td>Express</td></tr>
        <tr><td>87</td><td>Delhivery 5KG Savex RVP (S)</td></tr>
        <tr><td>88</td><td>Delhivery 10KG Savex RVP (S)</td></tr>
        <tr><td>89</td><td>Delhivery 20KG Savex RVP (S)</td></tr>
        <tr><td>90</td><td>Standard 500gm</td></tr>
        <tr><td>91</td><td>Standard 2kg</td></tr>
        <tr><td>92</td><td>Standard 5kg</td></tr>
        <tr><td>93</td><td>Standard 10kg</td></tr>
        <tr><td>94</td><td>Standard 20kg</td></tr>
        <tr><td>95</td><td>Shadowfax Local</td></tr>
        <tr><td>97</td><td>Dunzo Local</td></tr>
        <tr><td>98</td><td>SRF Standard 5kg</td></tr>
        <tr><td>99</td><td>Ecom Express ROS Reverse</td></tr>
        <tr><td>100</td><td>Delhivery Surface 10 Kgs</td></tr>
        <tr><td>101</td><td>Delhivery Surface 20 Kgs</td></tr>
        <tr><td>106</td><td>Borzo</td></tr>
        <tr><td>107</td><td>Borzo 5 Kg</td></tr>
        <tr><td>114</td><td>SRF Standard 10kg</td></tr>
        <tr><td>115</td><td>Shadowfax Savex</td></tr>
        <tr><td>116</td><td>SRF Standard 20kg</td></tr>
        <tr><td>117</td><td>SRF Express</td></tr>
        <tr><td>118</td><td>Bluedart Savex Exchange</td></tr>
        <tr><td>119</td><td>Identify Plus SDD Lite</td></tr>
        <tr><td>120</td><td>Identify Plus SDD Standard</td></tr>
        <tr><td>125</td><td>Xpressbees Reverse</td></tr>
        <tr><td>126</td><td>Bluedart Savex TDD</td></tr>
        <tr><td>130</td><td>Ekart 10kg</td></tr>
        <tr><td>138</td><td>Delhivery Reverse 5kg</td></tr>
        <tr><td>140</td><td>SRX Premium</td></tr>
        <tr><td>141</td><td>SRF RUSH</td></tr>
        <tr><td>142</td><td>Amazon Surface 500gm Prepaid</td></tr>
        <tr><td>144</td><td>Xpressbees Reverse 2kg</td></tr>
        <tr><td>145</td><td>Xpressbees Reverse 5 kg</td></tr>
        <tr><td>146</td><td>Kerry Indev 2kg Surface</td></tr>
        <tr><td>150</td><td>Xpressbees Reverse 1kg</td></tr>
        <tr><td>154</td><td>Kerry Indev Express</td></tr>
        <tr><td>159</td><td>Xpressbees 10kg</td></tr>
        <tr><td>170</td><td>Ekart 2Kg</td></tr>
        <tr><td>171</td><td>Ekart 5Kg</td></tr>
        <tr><td>181</td><td>Amazon Shipping 10Kg</td></tr>
        <tr><td>182</td><td>Amazon Shipping 20Kg</td></tr>
        <tr><td>195</td><td>Amazon Surface 500gm COD</td></tr>
        <tr><td>196</td><td>DTDC 500GMS</td></tr>
        <tr><td>235</td><td>DTDC Express</td></tr>
        <tr><td>240</td><td>SRX Priority</td></tr>
        <tr><td>313</td><td>Wholemark</td></tr>
        <tr><td>501</td><td>Bluedart CE Savex</td></tr>
        <tr><td>524</td><td>Sm Shadowfax Surface 3Kg</td></tr>
        <tr><td>777</td><td>Shadowfax DS</td></tr>
    </table>
</body>

</html>