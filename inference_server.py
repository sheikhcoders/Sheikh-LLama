from fastapi import FastAPI, HTTPException
from pydantic import BaseModel
import torch
from transformers import AutoModelForCausalLM, AutoTokenizer
import uvicorn

app = FastAPI()

# Global variables for model and tokenizer
model = None
tokenizer = None
device = None

class GenerateRequest(BaseModel):
    prompt: str
    max_new_tokens: int = 50
    temperature: float = 0.7
    top_p: float = 0.95
    top_k: int = 50

@app.on_event("startup")
def load_model():
    global model, tokenizer, device
    model_path = "./Sheikh-ABF"
    print(f"Loading model from {model_path}...")
    tokenizer = AutoTokenizer.from_pretrained(model_path)
    model = AutoModelForCausalLM.from_pretrained(model_path)

    device = "cuda" if torch.cuda.is_available() else "cpu"
    model.to(device)
    model.eval()
    print(f"Model loaded on {device}")

@app.post("/generate")
async def generate(request: GenerateRequest):
    try:
        input_ids = tokenizer.encode(request.prompt, return_tensors='pt').to(device)

        with torch.no_grad():
            output_ids = model.generate(
                input_ids,
                max_new_tokens=request.max_new_tokens,
                do_sample=True,
                temperature=request.temperature,
                top_p=request.top_p,
                top_k=request.top_k,
                pad_token_id=tokenizer.pad_token_id,
                eos_token_id=tokenizer.eos_token_id
            )

        generated_text = tokenizer.decode(output_ids[0], skip_special_tokens=False)
        return {
            "generated_text": generated_text,
            "prompt": request.prompt
        }
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

if __name__ == "__main__":
    uvicorn.run(app, host="127.0.0.1", port=5000)
