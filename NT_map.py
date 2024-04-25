import folium
import requests
import numpy as np
import pandas as pd
import mysql.connector


db_connection = mysql.connector.connect(
    host="localhost",  
    user="root",  
    passwd="testpassword",  
    database="Flora"  
)


cursor = db_connection.cursor()


query = "SELECT * FROM NT_Map"
cursor.execute(query)


data = cursor.fetchall()

columns = [
    'Plant_ID', 
    'Plant_Code', 
    'Botanical_Name', 
    'Common_Name', 
    'Previous_Name', 
    'Plant_Type', 
    'Water_Needs', 
    'Climate_Zones', 
    'Light_Needs', 
    'Soil_Type', 
    'Soil_Additional', 
    'Maintenance', 
    'Abcission', 
    'Height_Ranges', 
    'Spread_Ranges', 
    'Flower_colour', 
    'Foliage_Colour', 
    'Perfume', 
    'Aromatic', 
    'Edible', 
    'Bird_Attracting', 
    'Bird_Attractant', 
    'Bore_water_Tolerance', 
    'Frost_Tolerance', 
    'Greywater_Tolerance', 
    'Native', 
    'Butterfly_Attracting', 
    'Butterfly_Type', 
    'FAMILY', 
    'GENUS', 
    'SPECIES', 
    'INFRA_SP_RANK', 
    'INFRA_SP_NAME', 
    'TAXON_NAME', 
    'Latitude', 
    'Longitude'
]

nt_df = pd.DataFrame(data, columns=columns)


# Define color codes for different plant types
color_dict = {
    'Shrub': 'blue',
    'Aquatic': 'green',
    'Medium Tree': 'red',
    'Perennial': 'orange',
    'Grass': 'purple',
    'Annual': 'pink',
    'Bulb': 'gray',
    'Fern': 'brown',
    'Climber': 'cyan',
    'Small Tree': 'magenta',
    'Groundcover': 'lime',
    'Perennial': 'olive',
    'Herb' : 'yellow'
}

# Create a map centered around Victoria, Australia
m = folium.Map(location=[-19.42, 133.36], zoom_start=7)

# Function to fetch image URL from Wikimedia Commons

def get_image_url(plant_name):
    search_url = "https://commons.wikimedia.org/w/api.php"
    search_params = {
        "action": "query",
        "list": "search",
        "srsearch": plant_name,
        "srnamespace": "6",
        "srlimit": "1",
        "format": "json"
    }
    
    try:
        search_response = requests.get(search_url, params=search_params)
        search_response.raise_for_status()

        search_data = search_response.json()
        if "query" in search_data and "search" in search_data["query"]:
            search_results = search_data["query"]["search"]
            if search_results:
                title = search_results[0]["title"]
                
                image_info_url = f"https://commons.wikimedia.org/w/api.php"
                image_info_params = {
                    "action": "query",
                    "prop": "imageinfo",
                    "titles": title,
                    "iiprop": "url",
                    "format": "json"
                }

                image_info_response = requests.get(image_info_url, params=image_info_params)
                image_info_response.raise_for_status()

                image_info_data = image_info_response.json()
                if "query" in image_info_data and "pages" in image_info_data["query"]:
                    pages = image_info_data["query"]["pages"]
                    if "-1" not in pages:
                        image_info = pages[next(iter(pages))]
                        image_url = image_info["imageinfo"][0]["url"]
                        return image_url
                    else:
                        print(f"No image found for {plant_name}.")
                else:
                    print("Image info not found in response.")
            else:
                print(f"No search results found for {plant_name}.")
        else:
            print("No query or search data in response.")
    except requests.RequestException as e:
        print(f"Error fetching data for {plant_name}: {e}")
    
    return None

# Add markers for each plant location with a random offset
for index, row in nt_df.iterrows():
    lat = float(row['Latitude']) + np.random.uniform(-2.8, 2.8)  
    lon = float(row['Longitude']) + np.random.uniform(-3.2, 3.2) 
    plant_type = row['Plant_Type']
    water_needs = row['Water_Needs']
    soil_type = row['Soil_Type']
    light_needs = row['Light_Needs']
    climate_zones = row['Climate_Zones']
    color = color_dict.get(plant_type, 'black')
    plant_name = row['Common_Name']
    
    # Fetch image URL for the current plant
    image_url = get_image_url(plant_name)
    
    # Construct tooltip with plant name, water needs, soil type, light needs, climate zones, and image
    tooltip_content = f"<div style='font-size: 16px; text-align: center;'><b>{plant_name}</b><br><br><b>Water Needs:</b> {water_needs}<br><b>Soil Type:</b> {soil_type}<br><b>Light Needs:</b> {light_needs}<br><b>Climate Zones:</b> {climate_zones}<br><br><img src='{image_url}' style='display: block; margin: auto;' width='300px'></div>" if image_url else f"<div style='font-size: 16px; text-align: center;'><b>{plant_name}</b><br><br><b>Water Needs:</b> {water_needs}<br><b>Soil Type:</b> {soil_type}<br><b>Light Needs:</b> {light_needs}<br><b>Climate Zones:</b> {climate_zones}</div>"
    
    # Add marker to the map
    folium.Marker(
        location=[lat, lon],
        tooltip=tooltip_content,
        icon=folium.Icon(color=color)
    ).add_to(m)

legend_html = '''
<div style="position: fixed; bottom: 50px; right: 50px; z-index: 1000; background-color: transparent; border: 2px solid black; padding: 3px; max-height: 200px; overflow-y: auto;">
     <p style="font-size: 16px;"><b>Legend</b></p>
     <p><i class="fa fa-circle fa-lg" style="color:blue"></i> Shrub</p>
     <p><i class="fa fa-circle fa-lg" style="color:green"></i> Aquatic</p>
     <p><i class="fa fa-circle fa-lg" style="color:red"></i> Medium Tree</p>
     <p><i class="fa fa-circle fa-lg" style="color:orange"></i> Perennial</p>
     <p><i class="fa fa-circle fa-lg" style="color:purple"></i> Grass</p>
     <p><i class="fa fa-circle fa-lg" style="color:pink"></i> Annual</p>
     <p><i class="fa fa-circle fa-lg" style="color:gray"></i> Bulb</p>
     <p><i class="fa fa-circle fa-lg" style="color:brown"></i> Fern</p>
     <p><i class="fa fa-circle fa-lg" style="color:lime"></i> Climber</p>
     <p><i class="fa fa-circle fa-lg" style="color:magenta"></i> Small Tree</p>
     <p><i class="fa fa-circle fa-lg" style="color:lime"></i> Groundcover</p>
     <p><i class="fa fa-circle fa-lg" style="color:olive"></i> Herb, Perennial</p>
</div>
'''
m.get_root().html.add_child(folium.Element(legend_html))
# Save the map as an HTML file
map_file = "/var/www/html/wordpress/wp-content/themes/NT_plants_map.html" 
m.save(map_file)


db_connection.close()
