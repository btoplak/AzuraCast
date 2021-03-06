<?php
namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use OpenApi\Annotations as OA;
use Symfony\Component\Validator\Constraints as Assert;

use Cake\Chronos\Chronos;
use DateTime;

/**
 * @ORM\Table(name="station_playlists")
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 *
 * @OA\Schema(type="object")
 */
class StationPlaylist
{
    use Traits\TruncateStrings;

    public const DEFAULT_WEIGHT = 3;
    public const DEFAULT_REMOTE_BUFFER = 20;

    public const TYPE_DEFAULT = 'default';
    public const TYPE_SCHEDULED = 'scheduled';
    public const TYPE_ONCE_PER_X_SONGS = 'once_per_x_songs';
    public const TYPE_ONCE_PER_X_MINUTES = 'once_per_x_minutes';
    public const TYPE_ONCE_PER_HOUR = 'once_per_hour';
    public const TYPE_ONCE_PER_DAY = 'once_per_day';
    public const TYPE_ADVANCED = 'custom';

    public const SOURCE_SONGS = 'songs';
    public const SOURCE_REMOTE_URL ='remote_url';

    public const REMOTE_TYPE_STREAM = 'stream';
    public const REMOTE_TYPE_PLAYLIST = 'playlist';

    public const ORDER_RANDOM = 'random';
    public const ORDER_SHUFFLE = 'shuffle';
    public const ORDER_SEQUENTIAL = 'sequential';

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     *
     * @OA\Property(example=1)
     * @var int
     */
    protected $id;

    /**
     * @ORM\Column(name="station_id", type="integer")
     * @var int
     */
    protected $station_id;

    /**
     * @ORM\ManyToOne(targetEntity="Station", inversedBy="playlists")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="station_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     * @var Station
     */
    protected $station;

    /**
     * @ORM\Column(name="name", type="string", length=200)
     *
     * @Assert\NotBlank()
     * @OA\Property(example="Test Playlist")
     *
     * @var string
     */
    protected $name;

    /**
     * @ORM\Column(name="type", type="string", length=50)
     *
     * @Assert\Choice(choices={"default", "scheduled", "once_per_x_songs", "once_per_x_minutes", "once_per_hour", "once_per_day", "custom"})
     * @OA\Property(example="default")
     *
     * @var string
     */
    protected $type = self::TYPE_DEFAULT;

    /**
     * @ORM\Column(name="source", type="string", length=50)
     *
     * @Assert\Choice(choices={"songs", "remote_url"})
     * @OA\Property(example="songs")
     *
     * @var string
     */
    protected $source = self::SOURCE_SONGS;

    /**
     * @ORM\Column(name="playback_order", type="string", length=50)
     *
     * @Assert\Choice(choices={"random", "shuffle", "sequential"})
     * @OA\Property(example="shuffle")
     *
     * @var string
     */
    protected $order = self::ORDER_SHUFFLE;

    /**
     * @ORM\Column(name="remote_url", type="string", length=255, nullable=true)
     *
     * @OA\Property(example="http://remote-url.example.com/stream.mp3")
     *
     * @var string|null
     */
    protected $remote_url;

    /**
     * @ORM\Column(name="remote_type", type="string", length=25, nullable=true)
     *
     * @Assert\Choice(choices={"stream", "playlist"})
     * @OA\Property(example="stream")
     *
     * @var string|null
     */
    protected $remote_type = self::REMOTE_TYPE_STREAM;

    /**
     * @ORM\Column(name="remote_timeout", type="smallint")
     *
     * @OA\Property(example=0)
     *
     * @var int The total time (in seconds) that Liquidsoap should buffer remote URL streams.
     */
    protected $remote_buffer = 0;

    /**
     * @ORM\Column(name="is_enabled", type="boolean")
     *
     * @OA\Property(example=true)
     *
     * @var bool
     */
    protected $is_enabled = true;

    /**
     * @ORM\Column(name="is_jingle", type="boolean")
     *
     * @OA\Property(example=false)
     *
     * @var bool If yes, do not send jingle metadata to AutoDJ or trigger web hooks.
     */
    protected $is_jingle = false;

    /**
     * @ORM\Column(name="play_per_songs", type="smallint")
     *
     * @OA\Property(example=5)
     *
     * @var int
     */
    protected $play_per_songs = 0;

    /**
     * @ORM\Column(name="play_per_minutes", type="smallint")
     *
     * @OA\Property(example=120)
     *
     * @var int
     */
    protected $play_per_minutes = 0;

    /**
     * @ORM\Column(name="play_per_hour_minute", type="smallint")
     *
     * @OA\Property(example=15)
     *
     * @var int
     */
    protected $play_per_hour_minute = 0;

    /**
     * @ORM\Column(name="schedule_start_time", type="smallint")
     *
     * @OA\Property(example=900)
     *
     * @var int
     */
    protected $schedule_start_time = 0;

    /**
     * @ORM\Column(name="schedule_end_time", type="smallint")
     *
     * @OA\Property(example=2200)
     *
     * @var int
     */
    protected $schedule_end_time = 0;

    /**
     * @ORM\Column(name="schedule_days", type="string", length=50, nullable=true)
     *
     * @OA\Property(example="0,1,2,3")
     *
     * @var string
     */
    protected $schedule_days;

    /**
     * @ORM\Column(name="play_once_time", type="smallint")
     *
     * @OA\Property(example=1500)
     *
     * @var int
     */
    protected $play_once_time = 0;

    /**
     * @ORM\Column(name="play_once_days", type="string", length=50, nullable=true)
     *
     * @OA\Property(example="0,1,2,3")
     *
     * @var string
     */
    protected $play_once_days;

    /**
     * @ORM\Column(name="weight", type="smallint")
     *
     * @OA\Property(example=3)
     *
     * @var int
     */
    protected $weight = self::DEFAULT_WEIGHT;

    /**
     * @ORM\Column(name="include_in_requests", type="boolean")
     *
     * @OA\Property(example=true)
     *
     * @var bool
     */
    protected $include_in_requests = true;

    /**
     * @ORM\Column(name="include_in_automation", type="boolean")
     *
     * @OA\Property(example=false)
     *
     * @var bool
     */
    protected $include_in_automation = false;

    /**
     * @ORM\Column(name="interrupt_other_songs", type="boolean")
     *
     * @OA\Property(example=false)
     *
     * @var bool
     */
    protected $interrupt_other_songs = false;

    /**
     * @ORM\Column(name="loop_playlist_once", type="boolean")
     *
     * @OA\Property(example=false)
     *
     * @var bool Whether to loop the playlist at the end of its playback.
     */
    protected $loop_playlist_once = false;

    /**
     * @ORM\Column(name="play_single_track", type="boolean")
     *
     * @OA\Property(example=false)
     *
     * @var bool Whether to only play a single track from the specified playlist when scheduled.
     */
    protected $play_single_track = false;

    /**
     * @ORM\OneToMany(targetEntity="StationPlaylistMedia", mappedBy="playlist", fetch="EXTRA_LAZY")
     * @ORM\OrderBy({"weight" = "ASC"})
     * @var Collection
     */
    protected $media_items;

    public function __construct(Station $station)
    {
        $this->station = $station;

        $this->media_items = new ArrayCollection;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return Station
     */
    public function getStation(): Station
    {
        return $this->station;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getShortName(): string
    {
        return Station::getStationShortName($this->name);
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $this->_truncateString($name, 200);
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType(string $type): void
    {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getSource(): string
    {
        return $this->source;
    }

    /**
     * @param string $source
     */
    public function setSource(string $source): void
    {
        $this->source = $source;
    }

    /**
     * @return string
     */
    public function getOrder(): string
    {
        return $this->order;
    }

    /**
     * @param string $order
     */
    public function setOrder(string $order): void
    {
        $this->order = $order;
    }

    /**
     * @return null|string
     */
    public function getRemoteUrl(): ?string
    {
        return $this->remote_url;
    }

    /**
     * @param null|string $remote_url
     */
    public function setRemoteUrl(?string $remote_url): void
    {
        $this->remote_url = $remote_url;
    }

    /**
     * @return string
     */
    public function getRemoteType(): ?string
    {
        return $this->remote_type;
    }

    /**
     * @param null|string $remote_type
     */
    public function setRemoteType(?string $remote_type): void
    {
        $this->remote_type = $remote_type;
    }

    /**
     * @return int
     */
    public function getRemoteBuffer(): int
    {
        return $this->remote_buffer;
    }

    /**
     * @param int $remote_buffer
     */
    public function setRemoteBuffer(int $remote_buffer): void
    {
        $this->remote_buffer = $remote_buffer;
    }

    /**
     * @return bool
     */
    public function getIsEnabled(): bool
    {
        return $this->is_enabled;
    }

    /**
     * Indicates whether a playlist is enabled and has content which can be scheduled by an AutoDJ scheduler.
     *
     * @return bool
     */
    public function isPlayable(): bool
    {
        if (!$this->is_enabled) {
            return false;
        }

        if (self::SOURCE_SONGS === $this->source) {
            return ($this->media_items->count() > 0);
        }

        return true;
    }

    /**
     * @param bool $is_enabled
     */
    public function setIsEnabled(bool $is_enabled): void
    {
        $this->is_enabled = $is_enabled;
    }

    /**
     * @return bool
     */
    public function isJingle(): bool
    {
        return $this->is_jingle;
    }

    /**
     * @param bool $is_jingle
     */
    public function setIsJingle(bool $is_jingle): void
    {
        $this->is_jingle = $is_jingle;
    }

    /**
     * @return int
     */
    public function getPlayPerSongs(): int
    {
        return $this->play_per_songs;
    }

    /**
     * @param int $play_per_songs
     */
    public function setPlayPerSongs(int $play_per_songs): void
    {
        $this->play_per_songs = $play_per_songs;
    }

    /**
     * @return int
     */
    public function getPlayPerMinutes(): int
    {
        return $this->play_per_minutes;
    }

    /**
     * @param int $play_per_minutes
     */
    public function setPlayPerMinutes(int $play_per_minutes): void
    {
        $this->play_per_minutes = $play_per_minutes;
    }

    /**
     * @return int
     */
    public function getPlayPerHourMinute(): int
    {
        return $this->play_per_hour_minute;
    }

    /**
     * @param int $play_per_hour_minute
     */
    public function setPlayPerHourMinute(int $play_per_hour_minute): void
    {
        if ($play_per_hour_minute > 59 || $play_per_hour_minute < 0) {
            $play_per_hour_minute = 0;
        }

        $this->play_per_hour_minute = $play_per_hour_minute;
    }

    /**
     * @return int
     */
    public function getScheduleStartTime(): int
    {
        return (int)$this->schedule_start_time;
    }

    /**
     * @return string
     */
    public function getScheduleStartTimeText(): string
    {
        return self::formatTimeCodeForInput($this->schedule_start_time);
    }

    /**
     * @param int $schedule_start_time
     */
    public function setScheduleStartTime(int $schedule_start_time): void
    {
        $this->schedule_start_time = $schedule_start_time;
    }

    /**
     * @return int
     */
    public function getScheduleEndTime(): int
    {
        return (int)$this->schedule_end_time;
    }

    /**
     * @return string
     */
    public function getScheduleEndTimeText(): string
    {
        return self::formatTimeCodeForInput($this->schedule_end_time);
    }

    /**
     * @param int $schedule_end_time
     */
    public function setScheduleEndTime(int $schedule_end_time): void
    {
        $this->schedule_end_time = $schedule_end_time;
    }

    /**
     * @return int Get the duration of scheduled play time in seconds (used for remote URLs of indeterminate length).
     */
    public function getScheduleDuration(): int
    {
        if (self::TYPE_SCHEDULED !== $this->type) {
            return 0;
        }

        $start_time = self::getTimestamp($this->schedule_start_time);
        $end_time = self::getTimestamp($this->schedule_end_time);

        if ($start_time > $end_time) {
            return 86400 - ($start_time - $end_time);
        }

        return $end_time - $start_time;
    }

    /**
     * @return array|null
     */
    public function getScheduleDays(): ?array
    {
        return (!empty($this->schedule_days)) ? explode(',', $this->schedule_days) : null;
    }

    /**
     * @param array $schedule_days
     */
    public function setScheduleDays($schedule_days): void
    {
        $this->schedule_days = implode(',', (array)$schedule_days);
    }

    /**
     * @return int
     */
    public function getPlayOnceTime(): int
    {
        return $this->play_once_time;
    }

    /**
     * @return string
     */
    public function getPlayOnceTimeText(): string
    {
        return self::formatTimeCodeForInput($this->play_once_time);
    }

    /**
     * @param int $play_once_time
     */
    public function setPlayOnceTime(int $play_once_time): void
    {
        $this->play_once_time = $play_once_time;
    }

    /**
     * @return array
     */
    public function getPlayOnceDays(): array
    {
        return explode(',', $this->play_once_days);
    }

    /**
     * @param array $play_once_days
     */
    public function setPlayOnceDays($play_once_days): void
    {
        $this->play_once_days = implode(',', (array)$play_once_days);
    }

    /**
     * @return int
     */
    public function getWeight(): int
    {
        if ($this->weight < 1) {
            return self::DEFAULT_WEIGHT;
        }

        return $this->weight;
    }

    /**
     * @param int $weight
     */
    public function setWeight(int $weight): void
    {
        $this->weight = $weight;
    }

    /**
     * @return bool
     */
    public function getIncludeInRequests(): bool
    {
        return $this->include_in_requests;
    }

    /**
     * Indicates whether this playlist can be used as a valid source of requestable media.
     *
     * @return bool
     */
    public function isRequestable(): bool
    {
        return ($this->is_enabled && $this->include_in_requests);
    }

    /**
     * @param bool $include_in_requests
     */
    public function setIncludeInRequests(bool $include_in_requests): void
    {
        $this->include_in_requests = $include_in_requests;
    }

    /**
     * @return bool
     */
    public function getIncludeInAutomation(): bool
    {
        return $this->include_in_automation;
    }

    /**
     * @param bool $include_in_automation
     */
    public function setIncludeInAutomation(bool $include_in_automation): void
    {
        $this->include_in_automation = $include_in_automation;
    }

    /**
     * @return bool
     */
    public function interruptOtherSongs(): bool
    {
        return $this->interrupt_other_songs;
    }

    /**
     * @param bool $interrupt_other_songs
     */
    public function setInterruptOtherSongs(bool $interrupt_other_songs): void
    {
        $this->interrupt_other_songs = $interrupt_other_songs;
    }

    /**
     * @return bool
     */
    public function loopPlaylistOnce(): bool
    {
        return $this->loop_playlist_once;
    }

    /**
     * @param bool $loop_playlist_once
     */
    public function setLoopPlaylistOnce(bool $loop_playlist_once): void
    {
        $this->loop_playlist_once = $loop_playlist_once;
    }

    /**
     * @return bool
     */
    public function playSingleTrack(): bool
    {
        return $this->play_single_track;
    }

    /**
     * @param bool $play_single_track
     */
    public function setPlaySingleTrack(bool $play_single_track): void
    {
        $this->play_single_track = $play_single_track;
    }

    /**
     * @return Collection
     */
    public function getMediaItems(): Collection
    {
        return $this->media_items;
    }

    /**
     * Export the playlist into a reusable format.
     *
     * @param string $file_format
     * @param bool $absolute_paths
     * @param bool $with_annotations
     * @return string
     */
    public function export($file_format = 'pls', $absolute_paths = false, $with_annotations = false): string
    {
        $media_path = ($absolute_paths) ? $this->station->getRadioMediaDir().'/' : '';

        switch($file_format)
        {
            case 'm3u':
                $playlist_file = [];
                foreach ($this->media_items as $media_item) {
                    $media_file = $media_item->getMedia();
                    $media_file_path = $media_path . $media_file->getPath();
                    $playlist_file[] = $media_file_path;
                }

                return implode("\n", $playlist_file);
                break;

            case 'pls':
            default:
                $playlist_file = [
                    '[playlist]',
                ];

                $i = 0;
                foreach($this->media_items as $media_item) {
                    $i++;

                    $media_file = $media_item->getMedia();
                    $media_file_path = $media_path . $media_file->getPath();
                    $playlist_file[] = 'File'.$i.'='.$media_file_path;
                    $playlist_file[] = 'Title'.$i.'='.$media_file->getArtist().' - '.$media_file->getTitle();
                    $playlist_file[] = 'Length'.$i.'='.$media_file->getLength();
                    $playlist_file[] = '';
                }

                $playlist_file[] = 'NumberOfEntries='.$i;
                $playlist_file[] = 'Version=2';

                return implode("\n", $playlist_file);
                break;
        }
    }

    /**
     * Given a time code i.e. "2300", return a UNIX timestamp that can be used to format the time for display.
     *
     * @param string|int $time_code
     * @return int
     */
    public static function getTimestamp($time_code): int
    {
        return self::getDateTime($time_code)
            ->getTimestamp();
    }

    /**
     * Given a time code i.e. "2300", return a time suitable for HTML5 inputs, i.e. "23:00".
     *
     * @param string|int $time_code
     * @return string
     */
    public static function formatTimeCodeForInput($time_code): string
    {
        $now = Chronos::now(new \DateTimeZone(date_default_timezone_get()));
        return self::getDateTime($time_code, $now)
            ->format('H:i');
    }

    /**
     * Return a \DateTime object (or null) for a given time code, by default in the UTC time zone.
     *
     * @param string|int $time_code
     * @param Chronos|null $now
     * @return Chronos
     */
    public static function getDateTime($time_code, Chronos $now = null): Chronos
    {
        if ($now === null) {
            $now = Chronos::now(new \DateTimeZone('UTC'));
        }

        $time_code = str_pad($time_code, 4, '0', STR_PAD_LEFT);
        return $now->setTime(substr($time_code, 0, 2), substr($time_code, 2));
    }

    /**
     * Return the current UTC time in "time code" style.
     *
     * @param Chronos|null $now
     * @return int
     */
    public static function getCurrentTimeCode(Chronos $now = null): int
    {
        if ($now === null) {
            $now = Chronos::now(new \DateTimeZone('UTC'));
        }

        return (int)$now->format('Hi');
    }
}
