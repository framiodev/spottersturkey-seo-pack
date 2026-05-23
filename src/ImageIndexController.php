<?php

namespace SpottersTurkey\SeoPack;

use Flarum\Post\Post;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;
use Laminas\Diactoros\Response\JsonResponse;
use Illuminate\Support\Arr;

class ImageIndexController implements RequestHandlerInterface
{
    public function handle(Request $request): Response
    {
        try {
            $params = $request->getQueryParams();
            $debug  = Arr::get($params, 'debug', false);
            $limit  = 100;
            $page   = (int) Arr::get($params, 'page', 1);
            $offset = ($page - 1) * $limit;

            // DEBUG MODU: ?debug=1
            if ($debug) {
                $total = Post::count();

                $samples = Post::orderBy('id', 'desc')->take(3)->get()->map(function($p) {
                    $raw = $p->getRawOriginal('content');
                    return [
                        'id'              => $p->id,
                        'raw_start'       => substr($raw, 0, 400),
                        'accessor_start'  => substr($p->content, 0, 400),
                        'has_xml_raw'     => (stripos($raw, '<SPOTTER-IMAGE') !== false),
                        'has_bbcode'      => (stripos($p->content, '[spotter-image') !== false),
                    ];
                });

                $count_xml_raw = Post::where('content', 'LIKE', '%<SPOTTER-IMAGE%')->count();

                return new JsonResponse([
                    'debug'         => true,
                    'total_posts'   => $total,
                    'count_xml_raw' => $count_xml_raw,
                    'samples'       => $samples,
                ]);
            }

            // NORMAL MOD — TÜM postları tara (frontend sayfalayacak)
            $posts = Post::where('content', 'LIKE', '%<SPOTTER-IMAGE%')
                ->orderBy('id', 'desc')
                ->get();

            $images = [];
            foreach ($posts as $post) {
                // Ham XML içeriğini al (accessor'ı bypass et)
                $rawContent = $post->getRawOriginal('content');

                // <SPOTTER-IMAGE alt="..." id="..." url="..."> formatını yakala
                preg_match_all('/<SPOTTER-IMAGE\s([^>]+)>/i', $rawContent, $tags);

                foreach ($tags[1] as $attrString) {
                    $alt = '';
                    $id  = '';
                    $url = '';

                    if (preg_match('/\balt="([^"]*)"/i', $attrString, $m)) $alt = $m[1];
                    if (preg_match('/\bid="([^"]*)"/i',  $attrString, $m)) $id  = $m[1];
                    if (preg_match('/\burl="([^"]*)"/i', $attrString, $m)) $url = $m[1];

                    if (!$url) continue;

                    $discussion = $post->discussion;

                    $images[] = [
                        'post_id'          => $post->id,
                        'discussion_id'    => $discussion ? $discussion->id : null,
                        'discussion_title' => $discussion ? $discussion->title : 'İsimsiz Konu',
                        'discussion_url'   => $discussion
                            ? 'https://forum.spottersturkey.com/d/' . $discussion->id
                            : '',
                        'image_id'         => $id,
                        'url'              => $url,
                        'alt'              => $alt ?: 'Açıklama Belirtilmedi',
                    ];
                }
            }

            return new JsonResponse([
                'status' => 'success',
                'images' => $images,
                'page'   => $page,
                'count'  => count($images),
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
