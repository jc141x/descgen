<?php

class Entity
{
    private $name;
    private $hero;
    private $screen = array();
    private $desc;
    private $reqs;
    private $maybe_audio;
    private $lang_count;
    private $lang;
    private $langs;
    private $locale;
    private $platform;
    private $genres;

    public function fetch($appid, $format = "bb")
    {
        if (!$appid) {
            return Logger::warn("no appid");
        }
        
        $url = "https://store.steampowered.com/api/appdetails?appids=$appid&l=english";
        try {
            $response_arr = json_decode(file_get_contents($url), true)[$appid];
        } catch (Throwable$th) {
            return Logger::warn($th->getMessage());
        }
        if (!isset($response_arr['data'])) {
            return Logger::warn($th->getMessage);
        }
        $data = $response_arr['data'];
        $this->name = $data['name'];
        $this->hero = "https://cdn.akamai.steamstatic.com/steam/apps/$appid/library_hero.jpg";
        array_push($this->screen, $data['screenshots'][0]['path_full']);
        array_push($this->screen, $data['screenshots'][1]['path_full']);
        array_push($this->screen, $data['screenshots'][2]['path_full']);
        $this->desc = htmlspecialchars_decode($data["short_description"]);
        // requirements
        $reqs_raw = $data["pc_requirements"]["minimum"];
        $reqs = strip_tags(preg_filter("/<br>/", "\n", $reqs_raw));
        $reqs_arr = explode("\n", $reqs);
        $reqs_arr = preg_grep("/(Processor|Memory|Graphics):.*/", $reqs_arr);
        $this->reqs = implode("\n", $reqs_arr);
    
        // localizations
        $lang_raw = $data["supported_languages"];
        $langs = explode("<br>", $lang_raw);
        $this->maybe_audio = isset($langs[1]) ? "\n" . strip_tags($langs[1]) : "";
        $langs = explode(",", $langs[0]);
        $this->lang_count = count($langs);
        $this->lang = strip_tags(implode(",", $langs));
        $langs = str_replace("Simplified Chinese", "Chinese", $langs);
        $langs = str_replace("Spanish - Spain", "Spanish", $langs);
        $this->langs = str_replace("Portuguese - Brazil", "Portuguese", $langs);
        
        if ($this->lang_count > 2) {
            $this->locale = "MULTi" . $this->lang_count;
        } elseif ($this->lang_count == 2) {
            // we turn "English,German" into "ENG/GER"
            $this->locale = strtoupper(constant("Alpha3TCode::" . trim(strtoupper(str_replace('*', '', strip_tags($langs[0])))))) . "/" . strtoupper(constant("Alpha3TCode::" . trim(strtoupper(str_replace('*', '', strip_tags($langs[1]))))));
        } else {
            $this->locale = strtoupper(constant("Alpha3TCode::" . strtoupper(str_replace('*', '', strip_tags($langs[0])))));
        }
    
        $this->platform = $data["platforms"]["linux"] ? "Native" : "Wine";
    
        //array_push($this->genres, $data["genres"][0]["description"]);
        //array_push($this->genres, $data["genres"][1]["description"]);
        $this->genres = implode(",",array_map(
            fn($x): string => '"'.$x["description"].'"', $data["genres"]
        ));
    
        date_default_timezone_set('UTC');
        $this->date = date(DATE_RFC822);
        
        if ($format == "bb") {
            return $this->bb();
        } elseif ($format == "md") {
            return $this->md();
        } else {
            return Logger::warn("invalid format: " . $format);
        }
    }

    private function md() {

        return <<<EOD
            ---
            title: "{$this->name} - <version>"
            date: "{$this->date}"
            categories: ["{$this->platform}"]
            tags: [{$this->genres}]
            magnet: "<magnet link>"
            featured: "{$this->hero}"
            ---
            
            {{< lead >}}
            {$this->desc}
            {{< /lead >}}
            
            ## System Requirements
            {$this->reqs}

            ## Other info
            Languages: {$this->lang}{$this->maybe_audio}
            <--<mods included, collection titles, etc..>-->
            
            ## Features
            |||
            |-------------------------|-----|
            | Play without extracting | YES |
            | Blocks Non-LAN traffic  | YES |
            
            ## Download
            {{< download >}}
            
            ## Screenshots
            ![screenshot 1]({$this->screen[0]})
            ![screenshot 2]({$this->screen[1]})
            ![screenshot 3]({$this->screen[2]})

            EOD;
    }

    private function bb() {

        return <<<EOD
            [img]{$this->hero}[/img]
            [size=22]{$this->name} - <Version> - {$this->locale} - GNU/Linux {$this->platform} - jc141[/size]
                                   
            {$this->desc}
            
            [size=14][url=https://github.com/jc141x/portal]SETUP AND SUPPORT[/url][/size]
            Game requirements
            {$this->reqs}
            
            Other information
            Languages: {$this->lang}{$this->maybe_audio}
            
            Feature set
            Play without extracting, highly efficient usage of space.
            NON-LAN network activity of the game blocked by default, no data sent back to any third party.
            
            [img]{$this->screen[0]}[/img]
            [img]{$this->screen[1]}[/img]
            [img]{$this->screen[2]}[/img]

            EOD;
    }

}
