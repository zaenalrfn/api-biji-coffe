namespace App\Events;

use App\Models\OrderMessage;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;

    public function __construct(OrderMessage $message)
    {
        // Muat data sender (name) agar muncul di aplikasi
        $this->message = $message->load('sender:id,name');
    }

    public function broadcastOn(): array
    {
        // Channel privat khusus untuk satu order
        return [new PrivateChannel('order.chat.' . $this->message->order_id)];
    }
}