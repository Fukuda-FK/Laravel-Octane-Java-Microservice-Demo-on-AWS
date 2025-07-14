package com.example.demo;

import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PathVariable;
import org.springframework.web.bind.annotation.RestController;
import java.util.Map;
import java.util.HashMap;

@RestController
public class PriceController {

    @GetMapping("/price/{productId}")
    public Map<String, Object> getPrice(@PathVariable String productId) {
        // 本来はデータベースなどから価格を取得します
        Map<String, Object> response = new HashMap<>();
        response.put("productId", productId);
        response.put("price", 1980); // 固定で1980円を返す
        response.put("source", "Java API");
        return response;
    }
}