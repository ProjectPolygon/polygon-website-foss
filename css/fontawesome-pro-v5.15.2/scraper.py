import sys
import requests
import os

def download(path):
    sys.stdout.write('downloading '+path+'...')
    r = requests.get("https://pro.fontawesome.com/releases/v5.10.2/"+path)
    open(path, "wb").write(r.content)
    sys.stdout.write(' done!\n')

os.makedirs("css")

download("css/all.css")
download("css/brands.css")
download("css/solid.css")
download("css/regular.css")
download("css/light.css")
download("css/fontawesome.css")

os.makedirs("webfonts")

download("webfonts/fa-brands-400.eot")
download("webfonts/fa-brands-400.ttf")
download("webfonts/fa-brands-400.svg")
download("webfonts/fa-brands-400.woff")
download("webfonts/fa-brands-400.woff2")

download("webfonts/fa-duotone-900.eot")
download("webfonts/fa-duotone-900.ttf")
download("webfonts/fa-duotone-900.svg")
download("webfonts/fa-duotone-900.woff")
download("webfonts/fa-duotone-900.woff2")

download("webfonts/fa-solid-900.eot")
download("webfonts/fa-solid-900.ttf")
download("webfonts/fa-solid-900.svg")
download("webfonts/fa-solid-900.woff")
download("webfonts/fa-solid-900.woff2")

download("webfonts/fa-regular-400.eot")
download("webfonts/fa-regular-400.ttf")
download("webfonts/fa-regular-400.svg")
download("webfonts/fa-regular-400.woff")
download("webfonts/fa-regular-400.woff2")

download("webfonts/fa-light-300.eot")
download("webfonts/fa-light-300.ttf")
download("webfonts/fa-light-300.svg")
download("webfonts/fa-light-300.woff")
download("webfonts/fa-light-300.woff2")
