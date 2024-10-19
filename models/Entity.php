<?php

class Entity
{
    private $appid;
    private $name;
    private $hero;
    private $screen = array();
    private $desc;
    private $reqs;
    private $reqs_table;
    private $maybe_audio;
    private $lang_count;
    private $lang;
    private $lang_table;
    private $langs;
    private $locale;
    private $platform;
    private $date;
    private $ea = false;
    private $genres;

    public function fetch($appid, $format = "bb")
    {
        if (!$appid) {
            return Logger::warn("no appid");
        }
        $this->appid = $appid;
        $url = "https://store.steampowered.com/api/appdetails?appids=$appid&l=english";
        try {
            $response_arr = json_decode(file_get_contents($url), true)[$appid];
        } catch (Throwable$th) {
            return Logger::warn($th->getMessage());
        }
        if (!isset($response_arr['data'])) {
            return Logger::warn("no data");
        }
        $data = $response_arr['data'];
        $this->name = $data['name'];
        foreach ($data['genres'] as $genre) {
            if ($genre['description'] == "Early Access") {
                $this->ea = true;
            }
        }

        $this->genres = implode(", ", array_column($data['genres'], 'description'));

        $this->name .= $this->ea ? " (EA)" : "";
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

        foreach ($reqs_arr as $req) {
            $this->reqs_table .= "| " . str_replace(":", " | ", $req) . " |\n";
        }
    
        // localizations
        $lang_raw = $data["supported_languages"];
        $langs = explode("<br>", $lang_raw);
        $this->maybe_audio = isset($langs[1]) ? "\n" . strip_tags($langs[1]) : "";
        $langs = explode(",", $langs[0]);
        $this->lang_count = count($langs);
        $this->lang = strip_tags(implode(",", $langs));

        foreach ($langs as $lang) {
            $this->lang_table .= "| " . rtrim(strip_tags($lang), "*") . " | ";
            $this->lang_table .= str_contains($lang, "*") ? "YES" : "NO";
            $this->lang_table .= " |\n";
        }

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

    private function bb() {

        return <<<EOD
            [img]{$this->hero}[/img]
            [size=22]
            {$this->name} - <Version> - {$this->locale} - GNU/Linux {$this->platform} - jc141 (Appid={$this->appid})
            [/size]

            Genres: {$this->genres}
                                   
            {$this->desc}

            [size=14][url=https://gitlab.com/jc141x/portal/]SETUP AND SUPPORT[/url][/size] - Setup is not optional.
            System requirements
            A GNU/Linux OS based on Arch (EndeavourOS, Artix, CachyOS), Debian Sid (Sparky Rolling, Siduction) or Fedora (Ultramarine, Nobara).
            Vulkan capable GPU/APU

            {$this->reqs}
            
            Other info
            Languages: {$this->lang}{$this->maybe_audio}
            Language change access: In-game/File editing while mounted(specify which).

            Gamepad support: Confirmed/Not Working/Could not verify/Not necessary for this game

            Integrated features
            Run with no extraction due to the Dwarfs image format. Mods/configs can be added through overlay-storage directory.

            Filesystem isolation for the game files through the use of Bubblewrap. Executables have read-only access to the system except for /dev /tmp.

            All network activity of the executable blocked by default with Bubblewrap isolation. Useful for privacy and security concerns.

            Gamescope support. Scales to desktop resolution, isolates game from display server and provides many useful features.

            Global and local default configurations are available to users to pick which features to enable or not.

            [img]{$this->screen[0]}[/img]
            [img]{$this->screen[1]}[/img]
            [img]{$this->screen[2]}[/img]

            EOD;
    }

    private function md() {

        return <<<EOD
        ---
        title: "{$this->name} | {$this->locale}"
        
        # generated using "date -u --rfc-3339=seconds"
        date: {$this->date}
        lastmod: {$this->date}
        
        # Cover Image
        image: "{$this->hero}"
        
        # Release Info
        gameID: "STEAM:{$this->appid}"
        version: "<Version>"
        runtime: "{$this->platform}"
        
        # Download info
        magnet: "<Magnet>"
        size: "<Size>"
        ---
        
        {{% releases/info %}}
        
        {$this->desc}
        
        <!--more-->
        
        ## System Requirements
        
        |           | Required                                                      |
        |-----------|---------------------------------------------------------------|
        {$this->reqs_table}
        ## Other info
        
        ### Languages
        
        |                  | Full Audio Support?  |
        |------------------|----------------------|
        {$this->lang_table}
        Language change access: In-game/File editing while mounted(specify which).
        Gamepad support: Confirmed/Not Working/Could not verify/Not necessary for this game
        
        ## Integrated features
        
        - Play without extracting, highly efficient usage of space through the use of the DwarFS compression filesystem.
        - All network activity in the game is blocked by default; no data is sent back to any third party.
        - Filesystem isolation for the game files through the use of Bubblewrap. All user data is read-only to the game when it is run.
        - Gamescope support. Adds the ability to enable additional features such as FSR, NIS, HDR and other options used on SteamDeck.
        - Global and local default configurations are available to users to pick and choose which features to enable or not. (as well as general behavior)
        
        {{% releases/download %}}
        
        ## Screenshots
        
        ![Screenshot 1]({$this->screen[0]})
        
        ![Screenshot 2]({$this->screen[0]})
        
        ![Screenshot 3]({$this->screen[0]})
        
        EOD;
    }
}
